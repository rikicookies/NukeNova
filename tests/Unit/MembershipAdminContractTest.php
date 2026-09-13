<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipAdminContractTest extends TestCase
{
    public function testMembershipAdminIsProtectedAndUsesCsrfForChanges(): void
    {
        $root=dirname(__DIR__,2);
        $routes=(string)file_get_contents($root.'/routes/admin.php');
        $controller=(string)file_get_contents($root.'/app/Admin/MembershipsController.php');

        self::assertStringContainsString("'/admin/memberships'", $routes);
        self::assertStringContainsString("'/admin/memberships/{id}'", $routes);
        self::assertStringContainsString("'/admin/memberships/{id}/assign'", $routes);
        self::assertStringContainsString("'/admin/memberships/{id}/revoke'", $routes);
        self::assertStringContainsString("'memberships.manage'", $controller);
        self::assertStringContainsString('csrf->validate', $controller);
        self::assertStringContainsString("'membership.assigned'", $controller);
        self::assertStringContainsString("'membership.revoked'", $controller);
        self::assertStringContainsString('membership_history', $controller);
    }

    public function testMembershipPermissionAndNavigationAreBundled(): void
    {
        $root=dirname(__DIR__,2);
        $migration=(string)file_get_contents($root.'/database/migrations/2026_09_10_000019_add_membership_permission.php');
        $navigation=(string)file_get_contents($root.'/app/Core/Admin/AdminNavigationManager.php');

        self::assertStringContainsString("'memberships.manage'", $migration);
        self::assertStringContainsString("'super-administrator'", $migration);
        self::assertStringContainsString("'Memberships', '/admin/memberships', 'memberships.manage'", $navigation);
    }

    public function testExistingVipAudienceStillUsesTheSameEntitlement(): void
    {
        $root=dirname(__DIR__,2);
        $audience=(string)file_get_contents($root.'/app/Core/Access/AccessAudience.php');
        self::assertStringContainsString('MembershipManagerInterface', $audience);
        self::assertStringContainsString("->isVip((int)\$user['id'])", $audience);
        self::assertStringNotContainsString('EntitlementService::VIP', $audience);
    }
    public function testDedicatedMembershipMutationsStayPostAndCsrfProtected(): void
    {
        $root=dirname(__DIR__,2);
        $routes=(string)file_get_contents($root.'/routes/admin.php');
        $controller=(string)file_get_contents($root.'/app/Admin/MembershipsController.php');

        foreach([
            '/admin/memberships/{id}/assign',
            '/admin/memberships/{id}/extend',
            '/admin/memberships/{id}/schedule',
            '/admin/memberships/{id}/schedule/cancel',
            '/admin/memberships/{id}/revoke',
        ] as $route){
            self::assertStringContainsString("\$router->post('{$route}'",$routes);
            self::assertStringNotContainsString("\$router->get('{$route}'",$routes);
        }

        self::assertGreaterThanOrEqual(5,substr_count($controller,'$this->csrf->validate('));
        self::assertStringContainsString("'memberships.manage'",$controller);
    }


}
