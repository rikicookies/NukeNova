<?php

declare(strict_types=1);

namespace NovaNuke\Core\Modules;

use NovaNuke\Core\Database\Migration;
use PDO;
use RuntimeException;

final class ModuleMigrator
{
    public function __construct(private readonly PDO $database)
    {
    }

    /** @return list<string> */
    public function run(ModuleManifest $manifest): array
    {
        $directory = $manifest->path . '/database/migrations';
        $files = is_dir($directory) ? (glob($directory . '/*.php') ?: []) : [];
        sort($files, SORT_STRING);
        $executed = $this->executed($manifest->slug);
        $batch = $this->nextBatch($manifest->slug);
        $completed = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (isset($executed[$name])) {
                continue;
            }
            $migration = require $file;
            if (! $migration instanceof Migration) {
                throw new RuntimeException("Module migration must implement Migration: {$file}");
            }
            $migration->up($this->database);
            $statement = $this->database->prepare(
                'INSERT INTO module_migrations (module_slug, migration, batch, executed_at) '
                . 'VALUES (:module_slug, :migration, :batch, UTC_TIMESTAMP())'
            );
            $statement->execute(['module_slug' => $manifest->slug, 'migration' => $name, 'batch' => $batch]);
            $completed[] = $name;
        }

        return $completed;
    }

    public function rollbackAll(ModuleManifest $manifest): void
    {
        $statement = $this->database->prepare(
            'SELECT migration FROM module_migrations WHERE module_slug = :slug ORDER BY id DESC'
        );
        $statement->execute(['slug' => $manifest->slug]);
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $file = $manifest->path . '/database/migrations/' . basename((string) $name) . '.php';
            if (! is_file($file)) {
                throw new RuntimeException("Cannot roll back missing module migration: {$name}");
            }
            $migration = require $file;
            if (! $migration instanceof Migration) {
                throw new RuntimeException("Module migration must implement Migration: {$file}");
            }
            $migration->down($this->database);
            $delete = $this->database->prepare(
                'DELETE FROM module_migrations WHERE module_slug = :slug AND migration = :migration'
            );
            $delete->execute(['slug' => $manifest->slug, 'migration' => $name]);
        }
    }

    /** @return array<string, true> */
    private function executed(string $slug): array
    {
        $statement = $this->database->prepare('SELECT migration FROM module_migrations WHERE module_slug = :slug');
        $statement->execute(['slug' => $slug]);

        return array_fill_keys($statement->fetchAll(PDO::FETCH_COLUMN), true);
    }

    private function nextBatch(string $slug): int
    {
        $statement = $this->database->prepare(
            'SELECT COALESCE(MAX(batch), 0) FROM module_migrations WHERE module_slug = :slug'
        );
        $statement->execute(['slug' => $slug]);

        return ((int) $statement->fetchColumn()) + 1;
    }
}
