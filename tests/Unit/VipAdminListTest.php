<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class VipAdminListTest extends TestCase
{
    public function testVipListUsesWhitelistedFiltersAndLatestEntitlement(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root . '/app/Admin/UsersController.php');

        self::assertStringContainsString("['all', 'active', 'inactive', 'none']", $controller);
        self::assertStringContainsString('SELECT MAX(latest.id)', $controller);
        self::assertStringContainsString('vip_expiring_soon', $controller);
        self::assertStringContainsString("latest.entitlement = 'vip'", $controller);
        self::assertStringContainsString("\$vipFilter = (string) \$request->query('vip', 'all')", $controller);

        $routes = (string) file_get_contents($root . '/routes/admin.php');
        self::assertStringContainsString('->index($request)', $routes);
    }

    public function testVipListShowsStatusFiltersAndAnEmptyState(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/users/index.twig');

        self::assertStringContainsString('Active VIP', $template);
        self::assertStringContainsString('Expired/revoked', $template);
        self::assertStringContainsString('Never VIP', $template);
        self::assertStringContainsString('within 7 days', $template);
        self::assertStringContainsString('No users match this VIP filter.', $template);
    }
}
