<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminDashboardAccountOperationsTest extends TestCase
{
    public function testCreateAccountShortcutMatchesTheExistingServerAuthorization(): void
    {
        $dashboard = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Admin/AdminDashboardController.php');
        $users = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Admin/UsersController.php');

        self::assertStringContainsString("'users.manage'", $dashboard);
        self::assertStringContainsString("'users.assign_roles'", $dashboard);
        self::assertStringContainsString("permissions['users.create']", $dashboard);
        self::assertStringContainsString('isSuperAdministrator', $dashboard);
        self::assertStringContainsString("guard('users.manage')", $users);
        self::assertStringContainsString("guard('users.assign_roles')", $users);
        self::assertStringContainsString('isSuperAdministrator', $users);
    }

    public function testDashboardReusesAccountCreationAndVipFilterRoutes(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Admin/AdminDashboardService.php');

        self::assertStringContainsString("permissions['users.create']", $source);
        self::assertStringContainsString("'/admin/users/create'", $source);
        self::assertStringContainsString("tableExists('user_entitlements')", $source);
        self::assertStringContainsString('COUNT(DISTINCT user_id)', $source);
        self::assertStringContainsString('INTERVAL 7 DAY', $source);
        self::assertStringContainsString("'/admin/users?vip=active'", $source);
    }
}
