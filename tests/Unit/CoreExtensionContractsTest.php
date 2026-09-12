<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Search\src\SearchProviderRegistry;
use NovaNuke\Core\Search\SearchProviderInterface;
use NovaNuke\Core\Search\SearchProviderRegistryInterface;
use NovaNuke\Core\Search\SearchProviderResult;
use NovaNuke\Core\Search\SearchProvidersRegistering;
use NovaNuke\Core\Search\SearchQuery;
use NovaNuke\Core\Search\SearchResultItem;
use NovaNuke\Core\Sitemap\SitemapCollecting;
use PHPUnit\Framework\TestCase;

final class CoreExtensionContractsTest extends TestCase
{
    public function testSearchRegistryAndEventExposeCoreContracts(): void
    {
        $registry = new SearchProviderRegistry();
        self::assertInstanceOf(SearchProviderRegistryInterface::class, $registry);
        $event = new SearchProvidersRegistering($registry);
        self::assertSame($registry, $event->registry);

        $provider = new class implements SearchProviderInterface {
            public function type(): string { return 'example'; }
            public function label(): string { return 'Example'; }
            public function search(SearchQuery $query): SearchProviderResult
            {
                return new SearchProviderResult([
                    new SearchResultItem('example', 'Example', '/example', 'Example', '2026-09-10 00:00:00'),
                ], 1);
            }
        };
        $registry->add($provider);
        self::assertSame($provider, $registry->get('example'));
    }

    public function testLegacySearchAndSitemapNamesRemainAliasesOfCoreContracts(): void
    {
        self::assertTrue(interface_exists(\Modules\Search\src\SearchProviderInterface::class));
        self::assertSame(SearchProviderInterface::class, (new \ReflectionClass(\Modules\Search\src\SearchProviderInterface::class))->getName());
        self::assertInstanceOf(SearchQuery::class, new \Modules\Search\src\SearchQuery('term', 10, null));
        self::assertInstanceOf(SearchProviderResult::class, new \Modules\Search\src\SearchProviderResult([], 0));
        self::assertInstanceOf(SearchResultItem::class, new \Modules\Search\src\SearchResultItem('example', 'Title', '/example', '', '2026-09-10 00:00:00'));
        self::assertInstanceOf(SearchProvidersRegistering::class, new \Modules\Search\src\SearchProvidersRegistering(new SearchProviderRegistry()));
        self::assertInstanceOf(SitemapCollecting::class, new \Modules\Seo\src\SitemapCollecting());
    }
}
