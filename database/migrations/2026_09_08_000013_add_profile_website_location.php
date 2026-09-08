<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec('ALTER TABLE user_profiles ADD COLUMN website VARCHAR(255) NULL AFTER bio_format,ADD COLUMN location VARCHAR(120) NULL AFTER website');
    }
    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE user_profiles DROP COLUMN location,DROP COLUMN website');
    }
};
