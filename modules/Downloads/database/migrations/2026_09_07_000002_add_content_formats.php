<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE downloads ADD COLUMN description_format VARCHAR(20) NOT NULL DEFAULT 'html' AFTER description, ADD COLUMN requirements_format VARCHAR(20) NOT NULL DEFAULT 'html' AFTER requirements");
    }

    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE downloads DROP COLUMN requirements_format, DROP COLUMN description_format');
    }
};
