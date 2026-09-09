<?php

declare(strict_types=1);

namespace Modules\Wiki\src;

final readonly class WikiPageChanged
{
    public function __construct(public int $id, public string $path, public int $actorId)
    {
    }
}
