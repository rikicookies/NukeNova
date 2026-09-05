<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $column = $database->query("SHOW COLUMNS FROM users LIKE 'auth_version'")->fetchColumn();
        if ($column === false) {
            $database->exec(
                'ALTER TABLE users ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER password_hash'
            );
        }
        $database->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    request_ip VARCHAR(45) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY password_reset_token_hash_unique (token_hash),
    KEY password_reset_user_index (user_id),
    KEY password_reset_expiration_index (expires_at),
    CONSTRAINT password_reset_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP TABLE IF EXISTS password_reset_tokens');
        $database->exec('ALTER TABLE users DROP COLUMN auth_version');
    }
};
