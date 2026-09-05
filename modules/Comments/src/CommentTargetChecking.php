<?php

declare(strict_types=1);

namespace Modules\Comments\src;

final class CommentTargetChecking
{
    public bool $accepted = false;

    public function __construct(public readonly string $type, public readonly int $contentId)
    {
    }

    public function accept(): void { $this->accepted = true; }
}
