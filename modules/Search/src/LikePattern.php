<?php

declare(strict_types=1);

namespace Modules\Search\src;

final class LikePattern
{
    public static function contains(string $literal): string
    {
        return '%' . strtr($literal, ['=' => '==', '%' => '=%', '_' => '=_']) . '%';
    }
}
