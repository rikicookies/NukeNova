<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Menus\MenuTreeBuilder;
use PHPUnit\Framework\TestCase;

final class MenuTreeBuilderTest extends TestCase
{
    public function testItBuildsAnOrderedHierarchy(): void
    {
        $tree = (new MenuTreeBuilder())->build([
            ['id' => 3, 'parent_id' => 1, 'sort_order' => 10, 'title' => 'Child'],
            ['id' => 2, 'parent_id' => null, 'sort_order' => 10, 'title' => 'First'],
            ['id' => 1, 'parent_id' => null, 'sort_order' => 20, 'title' => 'Second'],
        ]);

        self::assertSame('First', $tree[0]['title']);
        self::assertSame('Second', $tree[1]['title']);
        self::assertSame('Child', $tree[1]['children'][0]['title']);
        self::assertSame([], $tree[0]['children']);
    }

    public function testOrphansAndUnreachableCyclesAreNotRenderedAsRootItems(): void
    {
        $tree = (new MenuTreeBuilder())->build([
            ['id' => 1, 'parent_id' => 2, 'sort_order' => 0],
            ['id' => 2, 'parent_id' => 1, 'sort_order' => 0],
            ['id' => 3, 'parent_id' => 99, 'sort_order' => 0],
        ]);

        self::assertSame([], $tree);
    }
}
