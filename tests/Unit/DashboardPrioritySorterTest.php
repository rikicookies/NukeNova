<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Admin\DashboardPrioritySorter;
use PHPUnit\Framework\TestCase;

final class DashboardPrioritySorterTest extends TestCase
{
    public function testItOrdersAttentionItemsByPriorityAndThenLabel(): void
    {
        $items = (new DashboardPrioritySorter())->sort([
            ['label' => 'Unpublished pages', 'priority' => 60],
            ['label' => 'Module issues', 'priority' => 100],
            ['label' => 'Unpublished downloads', 'priority' => 60],
            ['label' => 'Comments awaiting moderation', 'priority' => 90],
        ]);

        self::assertSame([
            'Module issues',
            'Comments awaiting moderation',
            'Unpublished downloads',
            'Unpublished pages',
        ], array_column($items, 'label'));
    }

    public function testItAcceptsAnEmptyQueue(): void
    {
        self::assertSame([], (new DashboardPrioritySorter())->sort([]));
    }
}
