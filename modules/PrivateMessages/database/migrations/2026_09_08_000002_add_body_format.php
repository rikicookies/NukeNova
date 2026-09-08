<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE private_messages ADD body_format VARCHAR(20) NOT NULL DEFAULT 'markdown' AFTER body");
    }

    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE private_messages DROP COLUMN body_format');
    }
};
