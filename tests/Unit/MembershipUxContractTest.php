<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipUxContractTest extends TestCase
{
    public function testAccountShowsScheduledMembershipWithoutPersistedVipFlag(): void
    {
        $root=dirname(__DIR__,2);
        $controller=(string)file_get_contents($root.'/app/Auth/AccountController.php');
        $view=(string)file_get_contents($root.'/resources/views/auth/profile-edit.twig');

        self::assertStringContainsString("'scheduled_membership' =>", $controller);
        self::assertStringContainsString('scheduled_membership.name', $view);
        self::assertStringContainsString('scheduled_membership.starts_at', $view);
        self::assertStringNotContainsString('is_vip', $view);
    }

    public function testAdminShowsRenewalShortcutsAndScheduledState(): void
    {
        $root=dirname(__DIR__,2);
        $view=(string)file_get_contents($root.'/resources/views/admin/memberships/show.twig');
        $dashboard=(string)file_get_contents($root.'/app/Core/Admin/AdminDashboardService.php');

        foreach ([30,90,365] as $days) {
            self::assertStringContainsString('value="{{ quick_days }}"', $view);
        }
        self::assertStringContainsString('days_remaining', $view);
        self::assertStringContainsString('starts_in_days', $view);
        self::assertStringContainsString('Scheduled VIP', $dashboard);
    }

    public function testSchedulingAndCancellationHaveCoreEvents(): void
    {
        $root=dirname(__DIR__,2);
        $names=(string)file_get_contents($root.'/app/Core/Events/EventName.php');
        $service=(string)file_get_contents($root.'/app/Core/Membership/MembershipService.php');
        $notifications=(string)file_get_contents($root.'/modules/Notifications/src/NotificationsModule.php');

        self::assertStringContainsString('MEMBERSHIP_SCHEDULED', $names);
        self::assertStringContainsString('MEMBERSHIP_SCHEDULE_CANCELLED', $names);
        self::assertStringContainsString('new MembershipScheduled', $service);
        self::assertStringContainsString('new MembershipScheduleCancelled', $service);
        self::assertStringContainsString('MembershipScheduled', $notifications);
        self::assertStringContainsString('MembershipScheduleCancelled', $notifications);
    }
}
