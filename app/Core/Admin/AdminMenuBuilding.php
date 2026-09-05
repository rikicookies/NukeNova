<?php

declare(strict_types=1);

namespace NovaNuke\Core\Admin;

use InvalidArgumentException;

final class AdminMenuBuilding
{
    /** @var list<array{label:string,url:string,permission:string}> */
    private array $items = [];

    public function add(string $label, string $url, string $permission): void
    {
        if ($label === '' || ! str_starts_with($url, '/') || $permission === '') {
            throw new InvalidArgumentException('Invalid administrative menu item.');
        }
        $this->items[] = compact('label', 'url', 'permission');
    }

    public function items(): array
    {
        return $this->items;
    }
}
