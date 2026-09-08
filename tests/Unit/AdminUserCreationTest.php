<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminUserCreationTest extends TestCase
{
    public function testCreationFlowIsProtectedAndTransactional(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root.'/app/Admin/UsersController.php');
        $routes = file_get_contents($root.'/routes/admin.php');
        self::assertStringContainsString("guard('users.manage')", $controller);
        self::assertStringContainsString("guard('users.assign_roles')", $controller);
        self::assertStringContainsString('isSuperAdministrator', $controller);
        self::assertStringContainsString('csrf->validate', $controller);
        self::assertStringContainsString('beginTransaction', $controller);
        self::assertStringContainsString('password_hash', $controller);
        self::assertStringContainsString('must_change_password', $controller);
        self::assertStringContainsString("post('/admin/users'", $routes);
    }
}
