<?php

declare(strict_types=1);

namespace Modules\Pages\src;

final class PageChanged
{
    public function __construct(public string $contentType, public int $id, public int $actorId)
    {
    }
}
