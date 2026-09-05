<?php

declare(strict_types=1);

namespace Modules\Search\src;

final readonly class SearchProvidersRegistering
{
    public function __construct(public SearchProviderRegistry $registry)
    {
    }
}
