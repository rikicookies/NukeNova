<?php

declare(strict_types=1);

namespace Modules\News\src;

final readonly class ContentChanged
{
    public function __construct(public string $type, public int $id, public int $actorId)
    {
    }
}
