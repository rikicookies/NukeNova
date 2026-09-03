<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec(<<<'SQL'
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(190) NOT NULL,
    `value` LONGTEXT NULL,
    `type` VARCHAR(32) NOT NULL DEFAULT 'string',
    group_name VARCHAR(100) NOT NULL DEFAULT 'general',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY settings_key_unique (`key`),
    KEY settings_group_index (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP TABLE IF EXISTS settings');
    }
};
