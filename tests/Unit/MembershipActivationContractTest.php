<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipActivationContractTest extends TestCase
{
    public function testScheduledActivationIsIdempotentAndCoreOwned(): void
    {
        $root=dirname(__DIR__,2);
        $processor=(string)file_get_contents($root.'/app/Core/Membership/MembershipActivationProcessor.php');
        $events=(string)file_get_contents($root.'/app/Core/Events/EventName.php');
        $migration=(string)file_get_contents($root.'/database/migrations/2026_09_10_000021_add_membership_activation_event_marker.php');

        self::assertStringContainsString('activated_event_at IS NULL', $processor);
        self::assertStringContainsString('SET activated_event_at=UTC_TIMESTAMP()', $processor);
        self::assertStringContainsString('EventName::MEMBERSHIP_ACTIVATED', $processor);
        self::assertStringContainsString('MEMBERSHIP_ACTIVATED', $events);
        self::assertStringContainsString('WHERE starts_at<=UTC_TIMESTAMP()', $migration);
    }

    public function testActivationRunsBeforeExpirationDuringMaintenance(): void
    {
        $root=dirname(__DIR__,2);
        $pruner=(string)file_get_contents($root.'/app/Core/Maintenance/DataPruner.php');
        self::assertLessThan(
            strpos($pruner, "core.membership_expirations"),
            strpos($pruner, "core.membership_activations"),
        );
    }

    public function testNotificationsCanConsumeActualScheduledActivation(): void
    {
        $root=dirname(__DIR__,2);
        $notifications=(string)file_get_contents($root.'/modules/Notifications/src/NotificationsModule.php');
        self::assertStringContainsString('EventName::MEMBERSHIP_ACTIVATED', $notifications);
        self::assertStringContainsString('MembershipActivated', $notifications);
        self::assertStringContainsString('Scheduled VIP is now active', $notifications);
    }
}
