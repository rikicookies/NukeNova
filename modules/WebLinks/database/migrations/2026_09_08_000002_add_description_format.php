<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE web_links ADD COLUMN description_format VARCHAR(20) NOT NULL DEFAULT 'html' AFTER description");
    }

    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE web_links DROP COLUMN description_format');
    }
};
