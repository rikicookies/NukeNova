<?php

declare(strict_types=1);

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;

$router->get('/admin', static function (Request $request, Container $container): Response {
    $auth = $container->get(AuthManager::class);
    $user = $auth->user();

    if ($user === null) {
        return Response::redirect('/login');
    }
    if (! $auth->isSuperAdministrator((int) $user['id'])) {
        return Response::html('Forbidden', 403);
    }

    return Response::html($container->get(ViewRenderer::class)->render('admin/dashboard.twig', [
        'user' => $user,
        'csrf_token' => $container->get(CsrfTokenManager::class)->token(),
    ]));
});
