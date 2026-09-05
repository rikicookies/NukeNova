<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Blocks\BlockVisibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlockVisibilityTest extends TestCase
{
    #[DataProvider('cases')]
    public function testPageRules(string $mode, array $patterns, string $path, bool $expected): void
    {
        self::assertSame($expected, (new BlockVisibility())->matches($mode, $patterns, $path));
    }

    public static function cases(): array
    {
        return [
            ['all', [], '/anything', true],
            ['only', ['/news/*'], '/news/article', true],
            ['only', ['/news/*'], '/downloads', false],
            ['except', ['/admin/*', '/login'], '/login', false],
            ['except', ['/news/*'], '/pages/about', true],
            ['only', [], '/', false],
        ];
    }
}
