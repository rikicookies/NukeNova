<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Membership\MembershipPlanCatalog;
use PHPUnit\Framework\TestCase;

final class MembershipPlanCatalogTest extends TestCase
{
    public function testItExposesTheExpectedManualPlans(): void
    {
        $plans=(new MembershipPlanCatalog())->all();

        self::assertSame(['free','vip-30','vip-90','vip-annual','vip-lifetime'], array_keys($plans));
        self::assertSame(30, $plans['vip-30']['days']);
        self::assertSame(90, $plans['vip-90']['days']);
        self::assertSame(365, $plans['vip-annual']['days']);
        self::assertNull($plans['vip-lifetime']['days']);
        self::assertTrue($plans['vip-lifetime']['lifetime']);
        self::assertNull($plans['free']['entitlement']);
    }
}
