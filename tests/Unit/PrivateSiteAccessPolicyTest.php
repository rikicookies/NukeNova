<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\System\PrivateSiteAccessPolicy;
use PHPUnit\Framework\TestCase;

final class PrivateSiteAccessPolicyTest extends TestCase
{
    public function testItBlocksAnonymousContentOnlyWhileEnabled(): void
    {
        $policy = new PrivateSiteAccessPolicy();
        self::assertFalse($policy->blocks('/news', false, false));
        self::assertFalse($policy->blocks('/news', true, true));
        self::assertTrue($policy->blocks('/news', true, false));
    }

    public function testAnonymousUsersRetainAccountAccessRoutes(): void
    {
        $policy = new PrivateSiteAccessPolicy();
        foreach (['/login', '/register', '/forgot-password', '/reset-password/token', '/verify-email/token', '/resend-verification', '/health', '/install'] as $path) {
            self::assertFalse($policy->blocks($path, true, false), $path);
        }
    }
}
