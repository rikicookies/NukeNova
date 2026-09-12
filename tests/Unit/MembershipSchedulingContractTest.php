<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipSchedulingContractTest extends TestCase
{
    public function testMembershipApiSupportsComputedSchedulingAndExtension(): void
    {
        $root=dirname(__DIR__,2);
        $contract=(string)file_get_contents($root.'/app/Core/Membership/MembershipManagerInterface.php');
        $service=(string)file_get_contents($root.'/app/Core/Membership/MembershipService.php');

        self::assertStringContainsString('nextScheduled(', $contract);
        self::assertStringContainsString('extendDays(', $contract);
        self::assertStringContainsString('schedule(', $contract);
        self::assertStringContainsString('cancelScheduled(', $contract);
        self::assertStringContainsString('entitlements->extend', $service);
        self::assertStringContainsString('entitlements->schedule', $service);
    }

    public function testSchedulingRejectsOverlapAndLifetimeConflicts(): void
    {
        $root=dirname(__DIR__,2);
        $source=(string)file_get_contents($root.'/app/Core/Access/EntitlementService.php');

        self::assertStringContainsString('Lifetime VIP cannot have a future overlapping membership.', $source);
        self::assertStringContainsString('Scheduled membership cannot overlap the active VIP period.', $source);
        self::assertStringContainsString('A future membership is already scheduled for this user.', $source);
    }

    public function testExtendingDoesNotResetRemainingTimeOrChangePlanIdentity(): void
    {
        $root=dirname(__DIR__,2);
        $source=(string)file_get_contents($root.'/app/Core/Access/EntitlementService.php');

        self::assertStringContainsString("new DateTimeImmutable((string)\$record['expires_at']", $source);
        self::assertStringNotContainsString("SET expires_at=:expires,plan_key='vip-custom'", $source);
        self::assertStringContainsString("SET expires_at=:expires,source=\\'manual\\'", $source);
        self::assertStringContainsString('expired_event_at=NULL', $source);
    }

    public function testAdminSchedulingActionsRemainPostAndCsrfProtected(): void
    {
        $root=dirname(__DIR__,2);
        $routes=(string)file_get_contents($root.'/routes/admin.php');
        $controller=(string)file_get_contents($root.'/app/Admin/MembershipsController.php');

        foreach ([
            '/admin/memberships/{id}/extend',
            '/admin/memberships/{id}/schedule',
            '/admin/memberships/{id}/schedule/cancel',
        ] as $route) {
            self::assertStringContainsString("\$router->post('".$route."'", $routes);
        }
        self::assertGreaterThanOrEqual(3, substr_count($controller, 'csrf->validate'));
    }

    public function testNoVipBooleanColumnWasAdded(): void
    {
        $root=dirname(__DIR__,2);
        foreach(glob($root.'/database/migrations/*.php')?:[] as $file){
            $source=(string)file_get_contents($file);
            self::assertDoesNotMatchRegularExpression('/\b(?:is_vip|vip_active)\b/i',$source,basename($file));
        }
    }
}
