<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Search\src\SearchProviderInterface;
use Modules\Search\src\SearchProviderRegistry;
use Modules\Search\src\SearchProviderResult;
use Modules\Search\src\SearchQuery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SearchProviderRegistryTest extends TestCase
{
    public function testItRegistersProvidersByUniqueType(): void
    {
        $registry = new SearchProviderRegistry(); $registry->add($this->provider('news'));
        self::assertSame(['news'], array_keys($registry->all()));
        self::assertSame('news', $registry->get('news')?->type());
    }

    public function testItRejectsDuplicateTypes(): void
    {
        $registry = new SearchProviderRegistry(); $registry->add($this->provider('news'));
        $this->expectException(RuntimeException::class); $registry->add($this->provider('news'));
    }

    private function provider(string $type): SearchProviderInterface
    {
        return new class($type) implements SearchProviderInterface {
            public function __construct(private readonly string $value) {}
            public function type(): string { return $this->value; }
            public function label(): string { return ucfirst($this->value); }
            public function search(SearchQuery $query): SearchProviderResult { return new SearchProviderResult([], 0); }
        };
    }
}
