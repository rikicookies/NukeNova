<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use NovaNuke\Core\Membership\MembershipStatusPresenter;
use PHPUnit\Framework\TestCase;

final class MembershipPresentationTest extends TestCase
{
    public function testFreeAndLifetimeStatesAreExplicit(): void
    {
        $presenter=new MembershipStatusPresenter();
        $free=$presenter->present(['vip'=>false,'name'=>'Free']);
        self::assertSame('free',$free['state']);
        self::assertSame('Free',$free['label']);

        $lifetime=$presenter->present(['vip'=>true,'lifetime'=>true,'name'=>'VIP Lifetime']);
        self::assertSame('lifetime',$lifetime['state']);
        self::assertNull($lifetime['days_remaining']);
    }

    public function testFiniteVipReportsApproximateDaysRemaining(): void
    {
        $now=new DateTimeImmutable('2026-09-10 12:00:00',new DateTimeZone('UTC'));
        $status=(new MembershipStatusPresenter())->present([
            'vip'=>true,
            'lifetime'=>false,
            'name'=>'VIP 30 Days',
            'expires_at'=>'2026-09-12 00:00:00',
        ],$now);

        self::assertSame('active',$status['state']);
        self::assertSame(2,$status['days_remaining']);
    }

    public function testScheduledMembershipReportsTimeUntilActivation(): void
    {
        $now=new DateTimeImmutable('2026-09-10 12:00:00',new DateTimeZone('UTC'));
        $scheduled=(new MembershipStatusPresenter())->scheduled([
            'starts_at'=>'2026-09-13 00:00:00',
            'name'=>'VIP 90 Days',
        ],$now);

        self::assertNotNull($scheduled);
        self::assertSame('scheduled',$scheduled['state']);
        self::assertSame(3,$scheduled['starts_in_days']);
    }
}
