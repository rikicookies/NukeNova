<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE web_links ADD audience VARCHAR(20) NOT NULL DEFAULT 'public' AFTER status");
        $database->exec('CREATE INDEX web_links_audience_index ON web_links (audience, status, created_at)');
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP INDEX web_links_audience_index ON web_links');
        $database->exec('ALTER TABLE web_links DROP COLUMN audience');
    }
};
