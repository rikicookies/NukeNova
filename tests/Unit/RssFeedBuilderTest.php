<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use DOMDocument;
use Modules\News\src\RssFeedBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RssFeedBuilderTest extends TestCase
{
    public function testItBuildsValidEscapedRssWithAbsoluteUrls(): void
    {
        $xml = (new RssFeedBuilder())->build('Nova & Friends', 'News <updates>', 'https://example.test/', 'es_MX', [[
            'title' => 'One & Two', 'slug' => 'one-two', 'summary' => 'A <safe> summary',
            'content' => '<p>Fallback</p>', 'published_at' => '2026-09-01 12:30:00',
            'category_name' => 'General', 'username' => 'riki',
        ]]);
        $document = new DOMDocument();

        self::assertTrue($document->loadXML($xml));
        self::assertSame('Nova & Friends News', $document->getElementsByTagName('title')->item(0)?->textContent);
        self::assertSame('https://example.test/news/one-two', $document->getElementsByTagName('guid')->item(0)?->textContent);
        self::assertSame('es-mx', $document->getElementsByTagName('language')->item(0)?->textContent);
        self::assertStringContainsString('One &amp; Two', $xml);
        self::assertStringContainsString('dc:creator', $xml);
    }

    public function testItRejectsUnsafeBaseUrls(): void
    {
        $this->expectException(RuntimeException::class);
        (new RssFeedBuilder())->build('NovaNuke', 'News', 'javascript://example.test', 'en', []);
    }
}
