<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\PrivateMessages\src\PrivateMessageSent;
use PHPUnit\Framework\TestCase;

final class PrivateMessageSentTest extends TestCase
{
    public function testPayloadContainsOnlyRoutingIdentifiers(): void
    {
        self::assertSame(
            ['recipientId' => 9, 'conversationId' => 12, 'messageKey' => '34'],
            get_object_vars(new PrivateMessageSent(9, 12, '34')),
        );
    }
}
