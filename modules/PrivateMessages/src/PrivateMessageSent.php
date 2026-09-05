<?php

declare(strict_types=1);

namespace Modules\PrivateMessages\src;

final readonly class PrivateMessageSent
{
    public function __construct(
        public int $recipientId,
        public int $conversationId,
        public string $messageKey,
    ) {
    }
}
