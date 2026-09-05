<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\AccountEmailInput;
use PHPUnit\Framework\TestCase;

final class AccountEmailInputTest extends TestCase
{
    public function testItNormalizesAValidAddress(): void
    {
        $result = (new AccountEmailInput())->validate(' New@Example.TEST ', 'secret');
        self::assertSame('new@example.test', $result['email']);
        self::assertSame([], $result['errors']);
    }

    public function testItRejectsInvalidInput(): void
    {
        $result = (new AccountEmailInput())->validate("bad\n@example", '');
        self::assertArrayHasKey('email', $result['errors']);
        self::assertArrayHasKey('password', $result['errors']);
    }
}
