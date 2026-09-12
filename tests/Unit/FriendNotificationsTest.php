<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FriendNotificationsTest extends TestCase
{
    public function testNotificationsListenForBothFriendEvents(): void
    {
        $module = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Notifications/src/NotificationsModule.php');
        self::assertStringContainsString('EventName::FRIEND_REQUESTED', $module);
        self::assertStringContainsString('EventName::FRIEND_ACCEPTED', $module);
        self::assertStringContainsString('instanceof FriendRequested', $module);
        self::assertStringContainsString('instanceof FriendAccepted', $module);
        self::assertSame(2, substr_count($module, "'/friends'"));
    }
}
