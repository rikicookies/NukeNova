<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Comments\src\CommentService;
use Modules\Media\src\MediaRepository;
use Modules\PrivateMessages\src\PrivateMessageService;
use NovaNuke\Core\Comments\CommentCreated;
use NovaNuke\Core\Comments\CommentProviderInterface;
use NovaNuke\Core\Comments\CommentTargetChecking;
use NovaNuke\Core\Media\MediaLibraryInterface;
use NovaNuke\Core\Media\MediaUsageChecking;
use NovaNuke\Core\Messaging\PrivateMessageComposerInterface;
use NovaNuke\Core\Messaging\PrivateMessageSent;
use NovaNuke\Core\Social\FriendAccepted;
use NovaNuke\Core\Social\FriendRequested;
use PHPUnit\Framework\TestCase;

final class OptionalModuleServiceContractTest extends TestCase
{
    public function testConcreteOptionalServicesImplementCoreContracts(): void
    {
        self::assertTrue(is_a(CommentService::class, CommentProviderInterface::class, true));
        self::assertTrue(is_a(MediaRepository::class, MediaLibraryInterface::class, true));
        self::assertTrue(is_a(PrivateMessageService::class, PrivateMessageComposerInterface::class, true));
    }

    public function testLegacyIntegrationPayloadNamesRemainAliases(): void
    {
        self::assertInstanceOf(CommentCreated::class, new \Modules\Comments\src\CommentCreated(1, 'news', 2, 'approved'));
        self::assertInstanceOf(CommentTargetChecking::class, new \Modules\Comments\src\CommentTargetChecking('news', 2));
        self::assertInstanceOf(MediaUsageChecking::class, new \Modules\Media\src\MediaUsageChecking('/uploads/example.jpg'));
        self::assertInstanceOf(PrivateMessageSent::class, new \Modules\PrivateMessages\src\PrivateMessageSent(1, 2, '3'));
        self::assertInstanceOf(FriendRequested::class, new \Modules\Friends\src\FriendRequested(1, 2));
        self::assertInstanceOf(FriendAccepted::class, new \Modules\Friends\src\FriendAccepted(1, 2));
    }
}
