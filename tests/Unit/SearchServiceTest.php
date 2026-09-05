<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Search\src\SafeHighlighter;
use Modules\Search\src\SearchProviderInterface;
use Modules\Search\src\SearchProviderRegistry;
use Modules\Search\src\SearchProviderResult;
use Modules\Search\src\SearchQuery;
use Modules\Search\src\SearchResultItem;
use Modules\Search\src\SearchService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SearchServiceTest extends TestCase
{
    public function testItMergesProvidersInPublicationOrderAndHighlightsSafely(): void
    {
        $registry = new SearchProviderRegistry();
        $registry->add($this->provider('news', [new SearchResultItem('news', 'Nova news', '/news/nova', 'A <b>Nova</b> story', '2026-09-01 10:00:00')]));
        $registry->add($this->provider('pages', [new SearchResultItem('pages', 'Nova page', '/pages/nova', '<script>x</script> Nova', '2026-09-02 10:00:00')]));
        $result = (new SearchService($registry, new SafeHighlighter()))->search('Nova', '', 1, null);
        self::assertSame(2, $result['total']);
        self::assertSame('pages', $result['items'][0]['type']);
        self::assertSame('<mark>Nova</mark> page', (string) $result['items'][0]['title_html']);
        self::assertStringNotContainsString('<script>', (string) $result['items'][0]['excerpt_html']);
    }

    public function testItCanRestrictAQueryToOneProvider(): void
    {
        $registry = new SearchProviderRegistry();
        $registry->add($this->provider('news', [new SearchResultItem('news', 'Nova', '/news/nova', 'Nova', '2026-09-01 10:00:00')]));
        $registry->add($this->provider('pages', [new SearchResultItem('pages', 'Nova', '/pages/nova', 'Nova', '2026-09-02 10:00:00')]));
        $result = (new SearchService($registry, new SafeHighlighter()))->search('Nova', 'news', 1, null);
        self::assertSame(1, $result['total']); self::assertSame('news', $result['items'][0]['type']);
    }

    public function testItRejectsShortAndUnknownQueries(): void
    {
        $service = new SearchService(new SearchProviderRegistry(), new SafeHighlighter());
        try { $service->search('x', '', 1, null); self::fail('Short term accepted.'); } catch (RuntimeException $error) { self::assertStringContainsString('between 2 and 100', $error->getMessage()); }
        $this->expectException(RuntimeException::class); $service->search('valid', 'missing', 1, null);
    }

    /** @param list<SearchResultItem> $items */
    private function provider(string $type, array $items): SearchProviderInterface
    {
        return new class($type, $items) implements SearchProviderInterface {
            public function __construct(private readonly string $value, private readonly array $items) {}
            public function type(): string { return $this->value; }
            public function label(): string { return ucfirst($this->value); }
            public function search(SearchQuery $query): SearchProviderResult { return new SearchProviderResult(array_slice($this->items, 0, $query->limit), count($this->items)); }
        };
    }
}
