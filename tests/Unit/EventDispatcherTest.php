<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Events\EventDispatcher;
use PHPUnit\Framework\TestCase;

final class EventDispatcherTest extends TestCase
{
    public function testItDispatchesListenersByPriority(): void
    {
        $events = new EventDispatcher();
        $payload = new class { public array $order = []; };
        $events->listen('page.rendering', static function (object $event): void {
            $event->order[] = 'low';
        }, -10);
        $events->listen('page.rendering', static function (object $event): void {
            $event->order[] = 'high';
        }, 20);

        self::assertSame($payload, $events->dispatch('page.rendering', $payload));
        self::assertSame(['high', 'low'], $payload->order);
        self::assertSame(2, $events->listenerCount('page.rendering'));
    }

    public function testUnknownEventsLeavePayloadUntouched(): void
    {
        $events = new EventDispatcher();
        $payload = new \stdClass();

        self::assertSame($payload, $events->dispatch('unknown', $payload));
    }
}
