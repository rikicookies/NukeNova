<?php

declare(strict_types=1);

use NovaNuke\Auth\AuthManager;
use NovaNuke\Admin\UserSettingsController;
use NovaNuke\Admin\RolesController;
use NovaNuke\Admin\UsersController;
use NovaNuke\Admin\ActivityLogsController;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\View\ViewRenderer;

$router->get('/admin', static function (Request $request, Container $container): Response {
    $auth = $container->get(AuthManager::class);
    $user = $auth->user();

    if ($user === null) {
        return Response::redirect('/login');
    }
    if (! $container->get(AuthorizationService::class)->allows((int) $user['id'], 'admin.access')) {
        return Response::html('Forbidden', 403);
    }

    return Response::html($container->get(ViewRenderer::class)->render('admin/dashboard.twig', [
        'user' => $user,
        'csrf_token' => $container->get(CsrfTokenManager::class)->token(),
    ]));
});

$userSettingsController = static fn (Container $container): UserSettingsController => new UserSettingsController(
    $container->get(AuthManager::class),
    $container->get(AuthorizationService::class),
    $container->get(SettingsRepository::class),
    $container->get(ActivityLogger::class),
    $container->get(CsrfTokenManager::class),
    $container->get(ViewRenderer::class),
);
$router->get('/admin/settings/users', static fn (Request $request, Container $container): Response =>
    $userSettingsController($container)->show()
);
$router->post('/admin/settings/users', static fn (Request $request, Container $container): Response =>
    $userSettingsController($container)->update($request)
);

$rolesController = static fn (Container $container): RolesController => new RolesController(
    $container->get(\PDO::class),
    $container->get(AuthManager::class),
    $container->get(AuthorizationService::class),
    $container->get(ActivityLogger::class),
    $container->get(CsrfTokenManager::class),
    $container->get(ViewRenderer::class),
);
$router->get('/admin/roles', static fn (Request $request, Container $container): Response =>
    $rolesController($container)->index()
);
$router->get('/admin/roles/{id}', static fn (Request $request, Container $container): Response =>
    $rolesController($container)->edit($request)
);
$router->post('/admin/roles/{id}', static fn (Request $request, Container $container): Response =>
    $rolesController($container)->update($request)
);

$usersController = static fn (Container $container): UsersController => new UsersController(
    $container->get(\PDO::class),
    $container->get(AuthManager::class),
    $container->get(AuthorizationService::class),
    $container->get(ActivityLogger::class),
    $container->get(CsrfTokenManager::class),
    $container->get(ViewRenderer::class),
);
$router->get('/admin/users', static fn (Request $request, Container $container): Response =>
    $usersController($container)->index()
);
$router->get('/admin/users/{id}', static fn (Request $request, Container $container): Response =>
    $usersController($container)->edit($request)
);
$router->post('/admin/users/{id}', static fn (Request $request, Container $container): Response =>
    $usersController($container)->update($request)
);

$logsController = static fn (Container $container): ActivityLogsController => new ActivityLogsController(
    $container->get(\PDO::class),
    $container->get(AuthManager::class),
    $container->get(AuthorizationService::class),
    $container->get(ViewRenderer::class),
);
$router->get('/admin/logs', static fn (Request $request, Container $container): Response =>
    $logsController($container)->index()
);
