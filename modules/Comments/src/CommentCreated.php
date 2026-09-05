<?php

declare(strict_types=1);

namespace Modules\Comments\src;

final readonly class CommentCreated
{
    public function __construct(public int $id, public string $contentType, public int $contentId, public string $status)
    {
    }
}
