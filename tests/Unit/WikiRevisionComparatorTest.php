<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiRevisionComparator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WikiRevisionComparatorTest extends TestCase
{
    public function testItMarksAddedRemovedAndUnchangedMarkdownLines(): void
    {
        $diff = (new WikiRevisionComparator())->compare("# Guide\r\nOld text\r\nShared", "# Guide\nNew text\nShared");

        self::assertSame(['unchanged', 'removed', 'added', 'unchanged'], array_column($diff, 'kind'));
        self::assertSame('Old text', $diff[1]['old']);
        self::assertNull($diff[1]['new']);
        self::assertNull($diff[2]['old']);
        self::assertSame('New text', $diff[2]['new']);
    }

    public function testItRejectsAnExcessivelyLargeChangedMiddle(): void
    {
        $old = implode("\n", array_fill(0, 501, 'old'));
        $new = implode("\n", array_fill(0, 501, 'new'));

        $this->expectException(RuntimeException::class);
        (new WikiRevisionComparator())->compare($old, $new);
    }
}
