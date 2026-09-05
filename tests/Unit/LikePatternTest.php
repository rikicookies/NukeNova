<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Search\src\LikePattern;
use PHPUnit\Framework\TestCase;

final class LikePatternTest extends TestCase
{
    public function testItEscapesSqlLikeMetacharacters(): void
    {
        self::assertSame('%100=%=_done\\now==%', LikePattern::contains('100%_done\\now='));
    }
}
