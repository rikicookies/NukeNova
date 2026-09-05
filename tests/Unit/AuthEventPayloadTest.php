<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\UserEmailVerified;
use NovaNuke\Auth\UserLoggedIn;
use NovaNuke\Auth\UserRegistered;
use PHPUnit\Framework\TestCase;

final class AuthEventPayloadTest extends TestCase
{
    public function testRegistrationPayloadContainsOnlyTheDocumentedState(): void
    {
        $event = new UserRegistered(42, true);
        self::assertSame(42, $event->userId);
        self::assertTrue($event->verificationRequired);
        self::assertSame(['userId', 'verificationRequired'], array_keys(get_object_vars($event)));
    }

    public function testIdentityEventsExposeOnlyTheUserId(): void
    {
        self::assertSame(['userId' => 7], get_object_vars(new UserEmailVerified(7)));
        self::assertSame(['userId' => 9], get_object_vars(new UserLoggedIn(9)));
    }
}
