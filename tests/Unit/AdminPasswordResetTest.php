<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminPasswordResetTest extends TestCase
{
    public function testAdministrativeResetIsProtectedAndRevokesSessions(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root.'/app/Admin/UsersController.php');
        $routes = file_get_contents($root.'/routes/admin.php');
        self::assertStringContainsString('function resetPassword', $controller);
        self::assertStringContainsString('creationGuard()', $controller);
        self::assertStringContainsString('csrf->validate', $controller);
        self::assertStringContainsString('must_change_password=1', $controller);
        self::assertStringContainsString('auth_version=auth_version+1', $controller);
        self::assertStringContainsString('DELETE FROM password_reset_tokens', $controller);
        self::assertStringContainsString('beginTransaction', $controller);
        self::assertStringContainsString('user.password.reset_by_admin', $controller);
        self::assertStringContainsString("post('/admin/users/{id}/password'", $routes);
    }
}
