<?php
declare(strict_types=1);
namespace Modules\Friends\src;
final readonly class FriendRequested { public function __construct(public int $recipientId,public int $requesterId) {} }
