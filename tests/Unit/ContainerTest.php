<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Container\Container;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function testItBuildsASharedServiceOnce(): void
    {
        $container = new Container();
        $container->bind('clock', static fn (): object => new \stdClass());

        self::assertSame($container->get('clock'), $container->get('clock'));
    }
}
