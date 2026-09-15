<?php

declare(strict_types=1);

namespace NovaNuke\Core\Modules;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Access\AccessAudience;
use NovaNuke\Core\I18n\Translator;

final class ModulesMenuBuilder
{
    public function __construct(
        private readonly ModuleManager $modules,
        private readonly AuthManager $auth,
        private readonly AccessAudience $audience,
        private readonly Translator $translator,
    ) {
    }

    /** @return list<array{slug:string,label:string,url:string,icon:?string,order:int}> */
    public function items(): array
    {
        $viewer = $this->auth->user();
        $items = [];
        foreach ($this->modules->inventory() as $slug => $module) {
            $manifest = $module['manifest'] ?? null;
            if (! ($manifest instanceof ModuleManifest)
                || ! (bool) ($module['installed'] ?? false)
                || ! (bool) ($module['enabled'] ?? false)
                || ! (bool) ($module['compatible'] ?? false)
                || ($module['last_error'] ?? null) !== null
                || $manifest->navigation === null
                || ! $this->audience->allows((string) ($module['audience'] ?? 'public'), $viewer)
                || ! $this->audience->allows($manifest->navigation['audience'], $viewer)) {
                continue;
            }
            $label = $manifest->navigation['label'];
            if (str_contains($label, '::')) $label = $this->translator->translate($label);
            $items[] = [
                'slug' => $slug,
                'label' => $label,
                'url' => $manifest->navigation['url'],
                'icon' => $manifest->navigation['icon'],
                'order' => $manifest->navigation['order'],
            ];
        }
        usort($items, static fn (array $left, array $right): int =>
            [$left['order'], $left['label'], $left['slug']] <=> [$right['order'], $right['label'], $right['slug']]
        );
        return $items;
    }
}
