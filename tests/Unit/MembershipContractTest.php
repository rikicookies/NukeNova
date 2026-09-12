<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipContractTest extends TestCase
{
    public function testCoreExposesAStorageIndependentMembershipContract(): void
    {
        $root=dirname(__DIR__,2);
        $contract=(string)file_get_contents($root.'/app/Core/Membership/MembershipManagerInterface.php');
        $service=(string)file_get_contents($root.'/app/Core/Membership/MembershipService.php');
        $application=(string)file_get_contents($root.'/app/Core/Application.php');

        self::assertStringContainsString('function status(int $userId): array', $contract);
        self::assertStringContainsString('function isVip(int $userId): bool', $contract);
        self::assertStringContainsString('function assign(', $contract);
        self::assertStringContainsString('function revoke(', $contract);
        self::assertStringContainsString('implements MembershipManagerInterface', $service);
        self::assertStringContainsString('MembershipManagerInterface::class', $application);
    }

    public function testAccountConsumesMembershipContractInsteadOfEntitlementStorage(): void
    {
        $root=dirname(__DIR__,2);
        $controller=(string)file_get_contents($root.'/app/Auth/AccountController.php');
        $routes=(string)file_get_contents($root.'/routes/account.php');
        $view=(string)file_get_contents($root.'/resources/views/auth/profile-edit.twig');

        self::assertStringContainsString('MembershipManagerInterface', $controller);
        self::assertStringContainsString("'membership' =>", $controller);
        self::assertStringNotContainsString('EntitlementService', $controller);
        self::assertStringContainsString('MembershipManagerInterface::class', $routes);
        self::assertStringContainsString('membership.vip', $view);
        self::assertStringContainsString('Free membership', $view);
    }

    public function testDashboardTreatsMembershipsAsTheirOwnAdministrativeCapability(): void
    {
        $root=dirname(__DIR__,2);
        $dashboard=(string)file_get_contents($root.'/app/Core/Admin/AdminDashboardService.php');

        self::assertStringContainsString("permissions['memberships.manage']", $dashboard);
        self::assertStringContainsString('Active VIP memberships', $dashboard);
        self::assertStringContainsString('Lifetime VIP', $dashboard);
        self::assertStringContainsString('/admin/memberships?status=expiring', $dashboard);
        self::assertStringContainsString('Manage memberships', $dashboard);
    }
}
