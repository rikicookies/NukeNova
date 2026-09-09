<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec('ALTER TABLE wiki_pages ADD comments_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER audience');
        $database->exec('ALTER TABLE wiki_page_revisions ADD comments_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER audience');
    }

    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE wiki_page_revisions DROP COLUMN comments_enabled');
        $database->exec('ALTER TABLE wiki_pages DROP COLUMN comments_enabled');
    }
};
