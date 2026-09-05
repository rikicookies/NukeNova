<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Menus\MenuUrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MenuUrlResolverTest extends TestCase
{
    #[DataProvider('validLinks')]
    public function testItResolvesSafeDestinations(string $type, string $target, string $expected): void
    {
        self::assertSame($expected, (new MenuUrlResolver())->resolve($type, $target));
    }

    public static function validLinks(): array
    {
        return [
            ['internal', '/', '/'],
            ['internal', '/news?page=2', '/news?page=2'],
            ['module', 'welcome', '/welcome'],
            ['external', 'https://example.com/path', 'https://example.com/path'],
        ];
    }

    #[DataProvider('dangerousLinks')]
    public function testItRejectsDangerousDestinations(string $type, string $target): void
    {
        $this->expectException(RuntimeException::class);
        (new MenuUrlResolver())->resolve($type, $target);
    }

    public static function dangerousLinks(): array
    {
        return [
            ['external', 'javascript:alert(1)'],
            ['external', 'data:text/html,bad'],
            ['internal', '//evil.example/path'],
            ['module', '../admin'],
        ];
    }
}
