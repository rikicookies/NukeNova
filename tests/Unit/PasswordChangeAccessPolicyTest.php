<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\System\PasswordChangeAccessPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordChangeAccessPolicyTest extends TestCase
{
    public function testItRestrictsForcedAccountsUntilPasswordChanges(): void
    {
        $policy = new PasswordChangeAccessPolicy();
        self::assertTrue($policy->blocks('/news', true));
        self::assertFalse($policy->blocks('/account/profile', true));
        self::assertFalse($policy->blocks('/account/password', true));
        self::assertFalse($policy->blocks('/logout', true));
        self::assertFalse($policy->blocks('/news', false));
    }
}
