<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Admin\AdminDashboardService;
use NovaNuke\Core\Admin\AdminMenuBuilding;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\System\SystemInspector;
use NovaNuke\Core\View\ViewRenderer;

final class AdminDashboardController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly AdminDashboardService $dashboard,
        private readonly SystemInspector $system,
        private readonly EventDispatcher $events,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
    ) {
    }

    public function index(): Response
    {
        $user = $this->auth->user();
        if ($user === null) return Response::redirect('/login');
        if (! $this->authorization->allows((int) $user['id'], 'admin.access')) return Response::html('Forbidden', 403);

        $permissionNames = [
            'users.view', 'news.edit', 'pages.edit', 'downloads.manage', 'comments.moderate',
            'roles.view', 'logs.view', 'modules.manage', 'settings.manage', 'themes.manage',
            'blocks.manage', 'menus.manage',
        ];
        $permissions = [];
        foreach ($permissionNames as $permission) {
            $permissions[$permission] = $this->authorization->allows((int) $user['id'], $permission);
        }

        $menu = new AdminMenuBuilding();
        $this->events->dispatch('admin.menu.building', $menu);
        $moduleLinks = array_values(array_filter(
            $menu->items(),
            fn (array $item): bool => $this->authorization->allows((int) $user['id'], $item['permission']),
        ));
        $systemWarnings = $permissions['settings.manage'] ? $this->system->inspect()['warnings'] : [];
        $coreLinks = array_values(array_filter([
            ['label' => 'Users', 'url' => '/admin/users', 'permission' => 'users.view'],
            ['label' => 'Roles & permissions', 'url' => '/admin/roles', 'permission' => 'roles.view'],
            ['label' => 'General settings', 'url' => '/admin/settings', 'permission' => 'settings.manage'],
            ['label' => 'Registration settings', 'url' => '/admin/settings/users', 'permission' => 'settings.manage'],
            ['label' => 'Activity logs', 'url' => '/admin/logs', 'permission' => 'logs.view'],
            ['label' => 'System information', 'url' => '/admin/system', 'permission' => 'settings.manage'],
            ['label' => 'Modules', 'url' => '/admin/modules', 'permission' => 'modules.manage'],
            ['label' => 'Themes', 'url' => '/admin/themes', 'permission' => 'themes.manage'],
            ['label' => 'Blocks', 'url' => '/admin/blocks', 'permission' => 'blocks.manage'],
            ['label' => 'Menus', 'url' => '/admin/menus', 'permission' => 'menus.manage'],
        ], static fn (array $link): bool => $permissions[$link['permission']] ?? false));

        return Response::html($this->views->render('admin/dashboard.twig', [
            'user' => $user,
            'csrf_token' => $this->csrf->token(),
            'module_admin_links' => $moduleLinks,
            'dashboard' => $this->dashboard->build($permissions),
            'system_warnings' => $systemWarnings,
            'core_admin_links' => $coreLinks,
        ]));
    }
}
