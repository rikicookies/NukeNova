<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\LoginValidator;
use PHPUnit\Framework\TestCase;

final class LoginValidatorTest extends TestCase
{
    public function testItAcceptsCredentialsWithReasonableLengths(): void
    {
        self::assertSame([], (new LoginValidator())->validate([
            'login' => 'riki',
            'password' => 'secret-value',
        ]));
    }

    public function testItRejectsMissingCredentials(): void
    {
        $errors = (new LoginValidator())->validate([]);

        self::assertArrayHasKey('login', $errors);
        self::assertArrayHasKey('password', $errors);
    }
}
