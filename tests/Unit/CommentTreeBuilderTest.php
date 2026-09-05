<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Comments\src\CommentTreeBuilder;
use PHPUnit\Framework\TestCase;

final class CommentTreeBuilderTest extends TestCase
{
    public function testItBuildsThreadedCommentsWithoutChangingTheirOrder(): void
    {
        $tree = (new CommentTreeBuilder())->build([
            ['id' => 1, 'parent_id' => null, 'body' => 'Root'],
            ['id' => 2, 'parent_id' => 1, 'body' => 'Reply'],
            ['id' => 3, 'parent_id' => null, 'body' => 'Second root'],
        ]);

        self::assertCount(2, $tree);
        self::assertSame('Root', $tree[0]['body']);
        self::assertSame('Reply', $tree[0]['children'][0]['body']);
        self::assertSame([], $tree[1]['children']);
    }

    public function testOrphanedCommentsAreNotRenderedAsRoots(): void
    {
        $tree = (new CommentTreeBuilder())->build([
            ['id' => 1, 'parent_id' => 99, 'body' => 'Orphan'],
        ]);

        self::assertSame([], $tree);
    }
}
