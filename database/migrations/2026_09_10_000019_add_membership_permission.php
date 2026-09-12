<?php

declare(strict_types=1);

use NovaNuke\Core\Database\Migration;

return new class implements Migration {
    public function up(PDO $database): void
    {
        $statement=$database->prepare(
            'INSERT INTO permissions (name,slug,description,module_slug,created_at,updated_at) '
            . 'VALUES (:name,:slug,:description,:module_slug,UTC_TIMESTAMP(),UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),updated_at=UTC_TIMESTAMP()'
        );
        $statement->execute([
            'name'=>'Manage memberships',
            'slug'=>'memberships.manage',
            'description'=>'Assign, replace and revoke manual membership plans.',
            'module_slug'=>'memberships',
        ]);
        $database->exec(
            "INSERT IGNORE INTO role_permissions (role_id,permission_id,created_at) "
            . "SELECT r.id,p.id,UTC_TIMESTAMP() FROM roles r CROSS JOIN permissions p "
            . "WHERE r.slug='super-administrator' AND p.slug='memberships.manage'"
        );
    }

    public function down(PDO $database): void
    {
        $statement=$database->prepare('DELETE FROM permissions WHERE slug=:slug');
        $statement->execute(['slug'=>'memberships.manage']);
    }
};
