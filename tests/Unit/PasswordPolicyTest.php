<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function testItAcceptsMatchingLongPasswords(): void
    {
        self::assertNull((new PasswordPolicy())->validate('a-long-password-value', 'a-long-password-value'));
    }

    public function testItRejectsShortOrMismatchedPasswords(): void
    {
        $policy = new PasswordPolicy();

        self::assertNotNull($policy->validate('short', 'short'));
        self::assertNotNull($policy->validate('a-long-password-value', 'a-different-password'));
    }
}
