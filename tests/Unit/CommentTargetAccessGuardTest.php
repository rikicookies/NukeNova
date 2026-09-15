<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Comments\src\CommentTargetAccessGuard;
use Modules\Comments\src\CommentTargetNotFound;
use NovaNuke\Core\Comments\CommentTargetChecking;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Events\EventName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommentTargetAccessGuardTest extends TestCase
{
    #[DataProvider('audiences')]
    public function testAudienceChecksAreEnforced(string $required, string $viewer, bool $allowed): void
    {
        $events = new EventDispatcher();
        $events->listen(EventName::COMMENTS_CONTENT_CHECKING, static function (object $event) use ($required, $viewer): void {
            if (! $event instanceof CommentTargetChecking || $event->type !== 'fixture' || $event->contentId !== 42) return;
            $ok = match ($required) {
                'public' => true,
                'member' => in_array($viewer, ['member', 'vip', 'role'], true),
                'vip' => $viewer === 'vip',
                'role' => $viewer === 'role',
                default => false,
            };
            if ($ok) $event->accept();
        });
        $guard = new CommentTargetAccessGuard($events);

        self::assertSame($allowed, $guard->allows('fixture', 42));
        if ($allowed) {
            $guard->require('fixture', 42);
            self::addToAssertionCount(1);
        } else {
            $this->expectException(CommentTargetNotFound::class);
            $guard->require('fixture', 42);
        }
    }

    public static function audiences(): array
    {
        return [
            'public guest' => ['public', 'guest', true],
            'member guest denied' => ['member', 'guest', false],
            'member allowed' => ['member', 'member', true],
            'vip member denied' => ['vip', 'member', false],
            'vip allowed' => ['vip', 'vip', true],
            'role member denied' => ['role', 'member', false],
            'role allowed' => ['role', 'role', true],
        ];
    }

    public function testInvalidOrUnknownTargetIsUniformlyNotFound(): void
    {
        $guard = new CommentTargetAccessGuard(new EventDispatcher());
        foreach ([['bad/type', 1], ['fixture', 0], ['fixture', 999]] as [$type, $id]) {
            self::assertFalse($guard->allows($type, $id));
            try {
                $guard->require($type, $id);
                self::fail('Expected target to be hidden.');
            } catch (CommentTargetNotFound $error) {
                self::assertSame('Not found.', $error->getMessage());
            }
        }
    }
}
