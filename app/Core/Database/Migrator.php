<?php

declare(strict_types=1);

namespace NovaNuke\Core\Database;

use PDO;
use RuntimeException;
use Throwable;

final class Migrator
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function ensureRepository(): void
    {
        $this->database->exec(
            'CREATE TABLE IF NOT EXISTS migrations ('
            . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
            . 'migration VARCHAR(255) NOT NULL UNIQUE,'
            . 'batch INT UNSIGNED NOT NULL,'
            . 'executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return list<string> */
    public function run(string $directory): array
    {
        $this->ensureRepository();
        $executed = $this->executed();
        $files = glob(rtrim($directory, '/') . '/*.php') ?: [];
        sort($files, SORT_STRING);
        $batch = $this->nextBatch();
        $completed = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (isset($executed[$name])) {
                continue;
            }

            $migration = require $file;

            if (! $migration instanceof Migration) {
                throw new RuntimeException("Migration must implement Migration: {$file}");
            }

            $this->database->beginTransaction();

            try {
                $migration->up($this->database);
                $statement = $this->database->prepare(
                    'INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)'
                );
                $statement->execute(['migration' => $name, 'batch' => $batch]);
                $this->database->commit();
                $completed[] = $name;
            } catch (Throwable $error) {
                if ($this->database->inTransaction()) {
                    $this->database->rollBack();
                }

                throw $error;
            }
        }

        return $completed;
    }

    /** @return array<string, true> */
    private function executed(): array
    {
        $names = $this->database->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

        return array_fill_keys($names, true);
    }

    private function nextBatch(): int
    {
        $current = $this->database->query('SELECT COALESCE(MAX(batch), 0) FROM migrations')->fetchColumn();

        return ((int) $current) + 1;
    }
}
