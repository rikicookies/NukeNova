<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    #[DataProvider('paths')]
    public function testItNormalizesPaths(string $uri, string $expected): void
    {
        self::assertSame($expected, Request::create('GET', $uri)->path());
    }

    public static function paths(): array
    {
        return [
            'root' => ['/', '/'],
            'trailing slash' => ['/news/', '/news'],
            'query string' => ['/news?page=2', '/news'],
        ];
    }
}
