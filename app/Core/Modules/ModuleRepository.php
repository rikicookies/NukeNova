<?php

declare(strict_types=1);

namespace NovaNuke\Core\Modules;

use PDO;

final class ModuleRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function available(): bool
    {
        $statement = $this->database->query("SHOW TABLES LIKE 'modules'");

        return $statement->fetchColumn() !== false;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        if (! $this->available()) {
            return [];
        }
        $rows = $this->database->query(
            'SELECT slug, name, installed_version, enabled, manifest, installed_at, updated_at, last_error FROM modules'
        )->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $row['enabled'] = (bool) $row['enabled'];
            $result[(string) $row['slug']] = $row;
        }

        return $result;
    }

    /** @return array<string, array<string, mixed>> */
    public function enabled(): array
    {
        return array_filter($this->all(), static fn (array $module): bool => $module['enabled']);
    }

    public function install(ModuleManifest $manifest): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO modules (slug, name, installed_version, enabled, manifest, installed_at, updated_at, last_error) '
            . 'VALUES (:slug, :name, :version, 0, :manifest, UTC_TIMESTAMP(), UTC_TIMESTAMP(), NULL) '
            . 'ON DUPLICATE KEY UPDATE name = VALUES(name), installed_version = VALUES(installed_version), '
            . 'manifest = VALUES(manifest), updated_at = UTC_TIMESTAMP(), last_error = NULL'
        );
        $statement->execute([
            'slug' => $manifest->slug,
            'name' => $manifest->name,
            'version' => $manifest->version,
            'manifest' => json_encode($manifest->toArray(), JSON_THROW_ON_ERROR),
        ]);
    }

    public function setEnabled(string $slug, bool $enabled): void
    {
        $statement = $this->database->prepare(
            'UPDATE modules SET enabled = :enabled, updated_at = UTC_TIMESTAMP(), last_error = NULL WHERE slug = :slug'
        );
        $statement->execute(['enabled' => $enabled ? 1 : 0, 'slug' => $slug]);
    }

    public function setError(string $slug, string $message): void
    {
        $statement = $this->database->prepare(
            'UPDATE modules SET last_error = :last_error, updated_at = UTC_TIMESTAMP() WHERE slug = :slug'
        );
        $statement->execute(['last_error' => mb_substr($message, 0, 4000), 'slug' => $slug]);
    }

    public function remove(string $slug): void
    {
        $statement = $this->database->prepare('DELETE FROM modules WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);
    }
}
