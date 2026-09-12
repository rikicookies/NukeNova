<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS quotes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_text VARCHAR(500) NOT NULL,
    attribution VARCHAR(120) NOT NULL DEFAULT '',
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_quotes_published (is_published, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP TABLE IF EXISTS quotes');
    }
};
