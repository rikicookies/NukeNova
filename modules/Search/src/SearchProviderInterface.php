<?php

declare(strict_types=1);

namespace Modules\Search\src;

interface SearchProviderInterface
{
    public function type(): string;

    public function label(): string;

    public function search(SearchQuery $query): SearchProviderResult;
}
