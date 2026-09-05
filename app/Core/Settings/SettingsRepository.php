<?php

declare(strict_types=1);

namespace NovaNuke\Core\Settings;

use PDO;

final class SettingsRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $statement = $this->database->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();

        if ($value === false) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public function setBoolean(string $key, bool $value, string $group = 'general'): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO settings (`key`, `value`, `type`, group_name, created_at, updated_at) '
            . 'VALUES (:key, :value, :type, :group_name, UTC_TIMESTAMP(), UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `type` = VALUES(`type`), '
            . 'group_name = VALUES(group_name), updated_at = UTC_TIMESTAMP()'
        );
        $statement->execute([
            'key' => $key,
            'value' => $value ? '1' : '0',
            'type' => 'boolean',
            'group_name' => $group,
        ]);
    }

    public function string(string $key, string $default = ''): string
    {
        $statement = $this->database->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
        $statement->execute(['key' => $key]);
        $value = $statement->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    public function setString(string $key, string $value, string $group = 'general'): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO settings (`key`, `value`, `type`, group_name, created_at, updated_at) '
            . 'VALUES (:key, :value, :type, :group_name, UTC_TIMESTAMP(), UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `type` = VALUES(`type`), '
            . 'group_name = VALUES(group_name), updated_at = UTC_TIMESTAMP()'
        );
        $statement->execute(['key' => $key, 'value' => $value, 'type' => 'string', 'group_name' => $group]);
    }
}
