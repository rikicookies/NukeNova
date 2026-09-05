<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Search\src\SafeHighlighter;
use PHPUnit\Framework\TestCase;

final class SafeHighlighterTest extends TestCase
{
    public function testItHighlightsWithoutTrustingResultHtml(): void
    {
        $html = (string) (new SafeHighlighter())->highlight('<script>alert(1)</script> Nova NOVA', 'nova');
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt; <mark>Nova</mark> <mark>NOVA</mark>', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function testRegexCharactersAreTreatedAsPlainText(): void
    {
        self::assertSame('Use <mark>C++</mark> safely', (string) (new SafeHighlighter())->highlight('Use C++ safely', 'C++'));
    }
}
