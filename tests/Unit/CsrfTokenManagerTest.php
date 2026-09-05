<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use PHPUnit\Framework\TestCase;

final class CsrfTokenManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testTokensAreSessionBoundAndRotationInvalidatesThePreviousValue(): void
    {
        $firstSession = new SessionManager('first_test_session', false);
        $first = new CsrfTokenManager($firstSession);
        $token = $first->token();

        self::assertSame(64, strlen($token));
        self::assertTrue($first->validate($token));
        self::assertFalse($first->validate('invalid'));

        $rotated = $first->rotate();
        self::assertNotSame($token, $rotated);
        self::assertFalse($first->validate($token));
        self::assertTrue($first->validate($rotated));

        $_SESSION = [];
        $second = new CsrfTokenManager(new SessionManager('second_test_session', false));
        self::assertFalse($second->validate($rotated));
    }
}
