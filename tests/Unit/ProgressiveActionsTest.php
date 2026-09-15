<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProgressiveActionsTest extends TestCase
{
    public function testSharedProgressiveEnhancementAssetIsLoadedByEveryBundledTheme(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['classic', 'default', 'novamodern'] as $theme) {
            $layout = file_get_contents($root . '/themes/' . $theme . '/layouts/default.twig');
            self::assertIsString($layout);
            self::assertStringContainsString('/assets/js/progressive-actions.js', $layout, $theme);
        }
    }

    public function testHighFrequencyFormsOptInWithoutChangingTheirPostFallback(): void
    {
        $root = dirname(__DIR__, 2);
        $modules = file_get_contents($root . '/resources/views/admin/modules/index.twig');
        $comments = file_get_contents($root . '/modules/Comments/views/thread.twig');
        $poll = file_get_contents($root . '/modules/Polls/views/show.twig');

        self::assertIsString($modules);
        self::assertStringContainsString('method="post"', $modules);
        self::assertStringContainsString('data-ajax-replace="#module-{{ slug }}"', $modules);

        self::assertIsString($comments);
        self::assertStringContainsString('action="/comments/{{ comment.id }}/react"', $comments);
        self::assertStringContainsString('data-ajax-replace="#comment-{{ comment.id }}"', $comments);

        self::assertIsString($poll);
        self::assertStringContainsString('action="/polls/{{ poll.id }}/vote"', $poll);
        self::assertStringContainsString('data-ajax-replace=".poll-detail"', $poll);
    }

    public function testProgressiveScriptKeepsNetworkFailureOnCurrentPage(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/progressive-actions.js');
        self::assertIsString($script);
        self::assertStringContainsString("document.addEventListener('submit'", $script);
        self::assertStringContainsString("credentials: 'same-origin'", $script);
        self::assertStringContainsString("new FormData(form)", $script);
        self::assertStringContainsString('Network error. The page was not changed.', $script);
        self::assertStringNotContainsString('window.location', $script);
    }
}
