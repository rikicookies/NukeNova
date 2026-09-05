<?php

declare(strict_types=1);

namespace NovaNuke\Core\Events;

use Closure;

final class EventDispatcher
{
    /** @var array<string, list<array{priority: int, listener: Closure}>> */
    private array $listeners = [];

    public function listen(string $event, Closure $listener, int $priority = 0): void
    {
        $this->listeners[$event][] = ['priority' => $priority, 'listener' => $listener];
        usort(
            $this->listeners[$event],
            static fn (array $left, array $right): int => $right['priority'] <=> $left['priority'],
        );
    }

    public function dispatch(string $event, object $payload): object
    {
        foreach ($this->listeners[$event] ?? [] as $registration) {
            ($registration['listener'])($payload);
        }

        return $payload;
    }

    public function listenerCount(string $event): int
    {
        return count($this->listeners[$event] ?? []);
    }
}
