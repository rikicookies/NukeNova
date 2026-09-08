<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE user_profiles ADD COLUMN bio_format VARCHAR(20) NOT NULL DEFAULT 'markdown' AFTER bio");
    }

    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE user_profiles DROP COLUMN bio_format');
    }
};
