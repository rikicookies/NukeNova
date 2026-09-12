<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipBetaStabilizationContractTest extends TestCase
{
    public function testFreeAdminStateUsesVipFlagInsteadOfGenericActiveFlag(): void
    {
        $root=dirname(__DIR__,2);
        $view=(string)file_get_contents($root.'/resources/views/admin/memberships/show.twig');

        self::assertStringContainsString('{% if membership_status.vip|default(false) %}', $view);
        self::assertStringNotContainsString('{% if membership_status and membership_status.active %}', $view);
    }

    public function testExtensionUiIsLimitedToFiniteActiveVip(): void
    {
        $root=dirname(__DIR__,2);
        $view=(string)file_get_contents($root.'/resources/views/admin/memberships/show.twig');

        self::assertStringContainsString('membership_status.vip|default(false) and not membership_status.lifetime|default(false)', $view);
        self::assertStringContainsString('Extend active VIP', $view);
    }

    public function testApplicationWiresMembershipMaintenanceProcessorsToDataPrunerOnly(): void
    {
        $root=dirname(__DIR__,2);
        $application=(string)file_get_contents($root.'/app/Core/Application.php');

        self::assertStringContainsString(
            'new AccountLifecycleService(' . "\n            \$c->get(PDO::class), \$c->get(EventDispatcher::class),\n        )",
            $application,
        );
        self::assertStringContainsString('new DataPruner(', $application);
        self::assertStringContainsString('MembershipActivationProcessor::class', $application);
        self::assertStringContainsString('MembershipExpirationProcessor::class', $application);
    }

    public function testCurrentRevokeDoesNotSilentlyCancelFutureMembership(): void
    {
        $root=dirname(__DIR__,2);
        $source=(string)file_get_contents($root.'/app/Core/Access/EntitlementService.php');
        $start=strpos($source, 'public function revoke(int $userId, string $entitlement)');
        self::assertNotFalse($start);
        $method=substr($source,$start,1200);

        self::assertStringContainsString('starts_at<=UTC_TIMESTAMP()', $method);
        self::assertStringNotContainsString('starts_at>UTC_TIMESTAMP()', $method);
    }

    public function testImmediateAssignmentsCarryActivationMarkerButScheduledGrantsDoNot(): void
    {
        $root=dirname(__DIR__,2);
        $source=(string)file_get_contents($root.'/app/Core/Access/EntitlementService.php');

        self::assertGreaterThanOrEqual(2, substr_count($source, 'activated_event_at'));
        $scheduleStart=strpos($source, 'public function schedule(');
        self::assertNotFalse($scheduleStart);
        $schedule=substr($source,$scheduleStart,6500);
        self::assertStringNotContainsString('activated_event_at,created_at', $schedule);
    }
}
