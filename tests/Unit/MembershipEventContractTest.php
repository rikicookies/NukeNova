<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipEventContractTest extends TestCase
{
    public function testMembershipLifecycleEventsAreCoreOwned(): void
    {
        $root=dirname(__DIR__,2);
        $names=(string)file_get_contents($root.'/app/Core/Events/EventName.php');
        foreach(['MEMBERSHIP_ASSIGNED','MEMBERSHIP_ACTIVATED','MEMBERSHIP_REVOKED','MEMBERSHIP_EXPIRED','MEMBERSHIP_SCHEDULED','MEMBERSHIP_SCHEDULE_CANCELLED'] as $constant){
            self::assertStringContainsString($constant,$names);
        }
        foreach(['MembershipAssigned.php','MembershipActivated.php','MembershipRevoked.php','MembershipExpired.php','MembershipScheduled.php','MembershipScheduleCancelled.php'] as $file){
            self::assertFileExists($root.'/app/Core/Membership/'.$file);
        }
    }

    public function testMembershipServiceDispatchesAssignedAndRevokedEvents(): void
    {
        $root=dirname(__DIR__,2);
        $service=(string)file_get_contents($root.'/app/Core/Membership/MembershipService.php');
        self::assertStringContainsString('EventName::MEMBERSHIP_ASSIGNED',$service);
        self::assertStringContainsString('EventName::MEMBERSHIP_REVOKED',$service);
        self::assertStringContainsString('new MembershipAssigned',$service);
        self::assertStringContainsString('new MembershipRevoked',$service);
    }

    public function testExpirationProcessorMarksAndDispatchesOnce(): void
    {
        $root=dirname(__DIR__,2);
        $processor=(string)file_get_contents($root.'/app/Core/Membership/MembershipExpirationProcessor.php');
        $migration=(string)file_get_contents($root.'/database/migrations/2026_09_10_000020_add_membership_expiration_event_marker.php');
        self::assertStringContainsString('expired_event_at IS NULL',$processor);
        self::assertStringContainsString('SET expired_event_at=UTC_TIMESTAMP()',$processor);
        self::assertStringContainsString('EventName::MEMBERSHIP_EXPIRED',$processor);
        self::assertStringContainsString('expired_event_at',$migration);
    }

    public function testNotificationsCanConsumeMembershipEventsWithoutEntitlementStorage(): void
    {
        $root=dirname(__DIR__,2);
        $module=(string)file_get_contents($root.'/modules/Notifications/src/NotificationsModule.php');
        self::assertStringContainsString('EventName::MEMBERSHIP_ASSIGNED',$module);
        self::assertStringContainsString('EventName::MEMBERSHIP_REVOKED',$module);
        self::assertStringContainsString('EventName::MEMBERSHIP_EXPIRED',$module);
        self::assertStringNotContainsString('user_entitlements',$module);
    }
}
