<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Security\SessionManager;
use PHPUnit\Framework\TestCase;

final class SessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testPullReturnsAndRemovesAFlashValue(): void
    {
        $session = new SessionManager('test_session', false);
        $session->put('notice', 'Saved');

        self::assertSame('Saved', $session->pull('notice'));
        self::assertNull($session->get('notice'));
        self::assertSame('fallback', $session->pull('missing', 'fallback'));
    }
}
