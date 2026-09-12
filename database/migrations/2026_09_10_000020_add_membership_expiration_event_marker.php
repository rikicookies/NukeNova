<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec(
            'ALTER TABLE user_entitlements ADD COLUMN expired_event_at DATETIME NULL AFTER revoked_at'
        );
        $database->exec(
            'CREATE INDEX user_entitlements_expiration_event_index '
            . 'ON user_entitlements (entitlement,expired_event_at,expires_at,revoked_at)'
        );
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP INDEX user_entitlements_expiration_event_index ON user_entitlements');
        $database->exec('ALTER TABLE user_entitlements DROP COLUMN expired_event_at');
    }
};
