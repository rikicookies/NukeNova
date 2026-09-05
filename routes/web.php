<?php

declare(strict_types=1);

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Security\CsrfTokenManager;

$router->get('/', static function (Request $request, Container $container): Response {
    $html = $container->get(ViewRenderer::class)->render('home.twig', [
        'cms_name' => 'NovaNuke',
        'version' => '0.1.0-dev',
        'user' => $container->get(AuthManager::class)->user(),
        'csrf_token' => $container->get(CsrfTokenManager::class)->token(),
    ]);

    return Response::html($html);
}, 'home');

$router->get('/health', static fn (): Response => Response::json([
    'status' => 'ok',
    'application' => 'NovaNuke',
]));

$router->get('/hello/{name}', static fn (Request $request): Response => Response::html(
    '<h1>Hello, ' . htmlspecialchars((string) $request->attribute('name'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>',
));
