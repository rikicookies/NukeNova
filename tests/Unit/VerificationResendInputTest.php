<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\VerificationResendInput;
use PHPUnit\Framework\TestCase;

final class VerificationResendInputTest extends TestCase
{
    public function testItNormalizesAValidEmail(): void
    {
        $result = (new VerificationResendInput())->validate(' Member@Example.TEST ');
        self::assertSame('member@example.test', $result['email']);
        self::assertNull($result['error']);
    }

    public function testItRejectsInvalidEmail(): void
    {
        self::assertNotNull((new VerificationResendInput())->validate('invalid')['error']);
    }
}
