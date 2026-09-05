<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Admin\DashboardContentMerger;
use PHPUnit\Framework\TestCase;

final class DashboardContentMergerTest extends TestCase
{
    public function testItCombinesContentNewestFirstAcrossModules(): void
    {
        $items = (new DashboardContentMerger())->merge([
            [['id' => 1, 'title' => 'News', 'created_at' => '2026-09-01 10:00:00']],
            [['id' => 2, 'title' => 'Page', 'created_at' => '2026-09-02 10:00:00']],
        ]);

        self::assertSame(['Page', 'News'], array_column($items, 'title'));
    }

    public function testItUsesTheIdentifierAsAStableTieBreakerAndAppliesLimit(): void
    {
        $items = (new DashboardContentMerger())->merge([[
            ['id' => 1, 'title' => 'First', 'created_at' => '2026-09-02 10:00:00'],
            ['id' => 3, 'title' => 'Latest ID', 'created_at' => '2026-09-02 10:00:00'],
        ]], 1);

        self::assertSame('Latest ID', $items[0]['title']);
    }

    public function testItHandlesNoOptionalContentProviders(): void
    {
        self::assertSame([], (new DashboardContentMerger())->merge([]));
    }
}
