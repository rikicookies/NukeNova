<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec('ALTER TABLE user_entitlements ADD COLUMN activated_event_at DATETIME NULL AFTER starts_at');
        $database->exec(
            "UPDATE user_entitlements SET activated_event_at=UTC_TIMESTAMP() "
            . "WHERE starts_at<=UTC_TIMESTAMP()"
        );
        $database->exec(
            'CREATE INDEX user_entitlements_activation_event_index '
            . 'ON user_entitlements (entitlement,activated_event_at,starts_at,revoked_at,expires_at)'
        );
    }

    public function down(PDO $database): void
    {
        $database->exec('DROP INDEX user_entitlements_activation_event_index ON user_entitlements');
        $database->exec('ALTER TABLE user_entitlements DROP COLUMN activated_event_at');
    }
};
