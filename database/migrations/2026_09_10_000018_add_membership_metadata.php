<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $database->exec("ALTER TABLE user_entitlements MODIFY expires_at DATETIME NULL");
        $database->exec("ALTER TABLE user_entitlements ADD COLUMN plan_key VARCHAR(64) NULL AFTER entitlement");
        $database->exec("ALTER TABLE user_entitlements ADD COLUMN source VARCHAR(32) NOT NULL DEFAULT 'manual' AFTER plan_key");
        $database->exec("ALTER TABLE user_entitlements ADD COLUMN note VARCHAR(255) NULL AFTER source");
        $database->exec("UPDATE user_entitlements SET plan_key='vip-custom' WHERE entitlement='vip' AND plan_key IS NULL");
        $database->exec("CREATE INDEX user_entitlements_plan_index ON user_entitlements (plan_key, revoked_at, expires_at)");
    }

    public function down(PDO $database): void
    {
        $database->exec("UPDATE user_entitlements SET expires_at='9999-12-31 23:59:59' WHERE expires_at IS NULL");
        $database->exec("DROP INDEX user_entitlements_plan_index ON user_entitlements");
        $database->exec("ALTER TABLE user_entitlements DROP COLUMN note");
        $database->exec("ALTER TABLE user_entitlements DROP COLUMN source");
        $database->exec("ALTER TABLE user_entitlements DROP COLUMN plan_key");
        $database->exec("ALTER TABLE user_entitlements MODIFY expires_at DATETIME NOT NULL");
    }
};
