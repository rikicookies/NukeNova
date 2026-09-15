<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProgressiveAccountActionsTest extends TestCase
{
    public function testNotificationActionsUseProgressiveEnhancement(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/modules/Notifications/views/index.twig');
        self::assertIsString($view);
        self::assertStringContainsString('action="/notifications/read-all"', $view);
        self::assertStringContainsString('data-ajax-replace=".notifications-page"', $view);
        self::assertStringContainsString('action="/notifications/{{ item.id }}/read"', $view);
    }

    public function testFriendActionsRefreshTheFriendsSurfaceWithoutNavigation(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/modules/Friends/views/index.twig');
        self::assertIsString($view);
        foreach (['accept', 'decline', 'remove', 'unblock'] as $action) {
            self::assertStringContainsString('/friends/' . $action . '/{{ person.id }}', $view);
        }
        self::assertGreaterThanOrEqual(4, substr_count($view, 'data-ajax-replace=".friends-page"'));
    }

    public function testThemeLifecycleActionsRefreshTheGridInPlace(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/themes/index.twig');
        self::assertIsString($view);
        self::assertStringContainsString('id="theme-grid"', $view);
        foreach (['install', 'update', 'activate', 'configure', 'uninstall'] as $action) {
            self::assertStringContainsString('/admin/themes/{{ slug }}/' . $action, $view);
        }
        self::assertGreaterThanOrEqual(5, substr_count($view, 'data-ajax-replace="#theme-grid"'));
    }

    public function testSharedScriptPreservesViewportWhenReplacingAFragment(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/progressive-actions.js');
        self::assertIsString($script);
        self::assertStringContainsString('const scrollY = window.scrollY;', $script);
        self::assertStringContainsString('window.scrollTo(scrollX, scrollY);', $script);
    }
}
