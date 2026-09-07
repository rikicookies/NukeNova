<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Blocks\BlockRendering;
use NovaNuke\Core\Blocks\DynamicBlockRenderer;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Logging\SensitiveDataRedactor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DynamicBlockRendererTest extends TestCase
{
    public function testItReturnsModuleHtml(): void
    {
        $events = new EventDispatcher();
        $events->listen('block.rendering', static function (object $event): void {
            if ($event instanceof BlockRendering) $event->render('<p>Dynamic</p>');
        });
        $renderer = new DynamicBlockRenderer($events, new SensitiveDataRedactor(), static fn (string $message) => null);

        self::assertSame('<p>Dynamic</p>', $renderer->render(['id' => 4, 'type' => 'sample']));
    }

    public function testItIsolatesAndRedactsProviderFailures(): void
    {
        $events = new EventDispatcher();
        $events->listen('block.rendering', static fn () => throw new RuntimeException('token=secret-value'));
        $messages = [];
        $renderer = new DynamicBlockRenderer(
            $events,
            new SensitiveDataRedactor(),
            static function (string $message) use (&$messages): void { $messages[] = $message; },
        );

        self::assertNull($renderer->render(['id' => 9, 'type' => 'polls-active']));
        self::assertCount(1, $messages);
        self::assertStringContainsString('polls-active (ID 9)', $messages[0]);
        self::assertStringNotContainsString('secret-value', $messages[0]);
    }
}
