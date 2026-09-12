<?php

declare(strict_types=1);

namespace NovaNuke\Core\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\View\ViewRenderer;

final class AdminNavigationManager
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly EventDispatcher $events,
        private readonly ViewRenderer $views,
    ) {
    }

    public function boot(): void
    {
        $path = '/' . trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        if ($path !== '/') $path = rtrim($path, '/');
        $adminArea = $path === '/admin' || str_starts_with($path, '/admin/');
        $this->views->addGlobal('admin_area', $adminArea);
        $this->views->addGlobal('admin_path', $path);
        $this->views->addGlobal('admin_navigation', $adminArea ? $this->navigation($path) : []);
    }

    /** @return list<array{slug:string,label:string,items:list<array<string,mixed>>}> */
    private function navigation(string $path): array
    {
        $user = $this->auth->user();
        if ($user === null || ! $this->authorization->allows((int) $user['id'], 'admin.access')) return [];

        $items = [
            $this->item('Dashboard', '/admin', 'admin.access', 'dashboard', 'overview'),
            $this->item('Users', '/admin/users', 'users.view', 'users', 'community'),
            $this->item('Memberships', '/admin/memberships', 'memberships.manage', 'badge', 'community'),
            $this->item('Roles & permissions', '/admin/roles', 'roles.view', 'shield', 'community'),
            $this->item('Themes', '/admin/themes', 'themes.manage', 'palette', 'appearance'),
            $this->item('Menus', '/admin/menus', 'menus.manage', 'menu', 'appearance'),
            $this->item('Blocks', '/admin/blocks', 'blocks.manage', 'blocks', 'appearance'),
            $this->item('Modules', '/admin/modules', 'modules.manage', 'module', 'system'),
            $this->item('General settings', '/admin/settings', 'settings.manage', 'settings', 'system'),
            $this->item('Registration settings', '/admin/settings/users', 'settings.manage', 'user-settings', 'system'),
            $this->item('Activity logs', '/admin/logs', 'logs.view', 'logs', 'system'),
            $this->item('System information', '/admin/system', 'settings.manage', 'system', 'system'),
        ];

        $modules = new AdminMenuBuilding();
        $this->events->dispatch(\NovaNuke\Core\Events\EventName::ADMIN_MENU_BUILDING, $modules);
        foreach ($modules->items() as $item) {
            $items[] = $this->moduleItem($item);
        }

        $groups = [
            'overview' => ['label' => 'Overview', 'items' => []],
            'content' => ['label' => 'Content', 'items' => []],
            'resources' => ['label' => 'Resources', 'items' => []],
            'community' => ['label' => 'Community', 'items' => []],
            'appearance' => ['label' => 'Appearance', 'items' => []],
            'system' => ['label' => 'System', 'items' => []],
            'modules' => ['label' => 'Other modules', 'items' => []],
        ];
        foreach ($items as $item) {
            if (! $this->authorization->allows((int) $user['id'], (string) $item['permission'])) continue;
            $item['active'] = $this->active($path, (string) $item['url']);
            $group = isset($groups[$item['group']]) ? $item['group'] : 'modules';
            $groups[$group]['items'][] = $item;
        }

        $result = [];
        foreach ($groups as $slug => $group) {
            if ($group['items'] !== []) $result[] = ['slug' => $slug, 'label' => $group['label'], 'items' => $group['items']];
        }
        return $result;
    }

    /** @return array{label:string,url:string,permission:string,icon:string,group:string} */
    private function item(string $label, string $url, string $permission, string $icon, string $group): array
    {
        return compact('label', 'url', 'permission', 'icon', 'group');
    }

    /** @param array{label:string,url:string,permission:string,icon:string,group:string} $item @return array{label:string,url:string,permission:string,icon:string,group:string} */
    private function moduleItem(array $item): array
    {
        $slug = explode('.', (string) $item['permission'])[0];
        $map = [
            'news' => ['newspaper', 'content'], 'pages' => ['page', 'content'], 'comments' => ['comments', 'content'],
            'media' => ['media', 'content'], 'downloads' => ['download', 'resources'], 'web-links' => ['link', 'resources'],
            'polls' => ['poll', 'resources'], 'search' => ['search', 'resources'], 'private-messages' => ['message', 'community'],
            'notifications' => ['bell', 'community'], 'statistics' => ['chart', 'overview'], 'seo' => ['search', 'system'],
        ];
        if ($item['icon'] === 'module' && isset($map[$slug])) $item['icon'] = $map[$slug][0];
        if ($item['group'] === 'modules' && isset($map[$slug])) $item['group'] = $map[$slug][1];
        return $item;
    }

    private function active(string $path, string $url): bool
    {
        if ($url === '/admin') return $path === '/admin';
        if ($url === '/admin/settings') return $path === '/admin/settings';
        return $path === $url || str_starts_with($path, $url . '/');
    }
}
