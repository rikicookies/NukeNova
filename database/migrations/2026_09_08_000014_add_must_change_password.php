<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec('ALTER TABLE users ADD must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash');
    }

    public function down(PDO $database): void
    {
        $database->exec('ALTER TABLE users DROP COLUMN must_change_password');
    }
};
