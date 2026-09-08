<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE news_articles ADD COLUMN summary_format VARCHAR(20) NOT NULL DEFAULT 'html' AFTER summary, ADD COLUMN content_format VARCHAR(20) NOT NULL DEFAULT 'html' AFTER content");
    }

    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE news_articles DROP COLUMN content_format, DROP COLUMN summary_format');
    }
};
