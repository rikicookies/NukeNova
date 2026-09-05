<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Modules\Seo\src\SitemapBuilder;
use Modules\Seo\src\SitemapCollecting;
use PHPUnit\Framework\TestCase;

final class SitemapTest extends TestCase
{
    public function testItDeduplicatesSortsAndEscapesPublicUrls(): void
    {
        $collection = new SitemapCollecting();
        $collection->add('/news/zeta', '2026-09-03 10:00:00', 'weekly', 0.8);
        $collection->add('/news/alpha', null, 'daily', 0.9);
        $collection->add('/news/alpha', null, 'weekly', 0.7);
        $xml = (new SitemapBuilder())->build('https://example.test/community', $collection);
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        self::assertSame(2, $xpath->query('//s:url')->length);
        self::assertSame('https://example.test/community/news/alpha', $xpath->evaluate('string((//s:loc)[1])'));
        self::assertSame('0.7', $xpath->evaluate('string((//s:priority)[1])'));
    }

    public function testItRejectsExternalOrQueryStringPaths(): void
    {
        $collection = new SitemapCollecting();
        $this->expectException(InvalidArgumentException::class);
        $collection->add('//attacker.example/path?token=secret');
    }

    public function testItRejectsUnsafeBaseUrls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SitemapBuilder())->build('javascript:alert(1)', new SitemapCollecting());
    }
}
