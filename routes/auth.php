<?php

declare(strict_types=1);

use NovaNuke\Auth\AuthController;
use NovaNuke\Auth\AuthManager;
use NovaNuke\Auth\LoginThrottle;
use NovaNuke\Auth\LoginValidator;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;

$authController = static fn (Container $container): AuthController => new AuthController(
    $container->get(AuthManager::class),
    $container->get(LoginThrottle::class),
    new LoginValidator(),
    $container->get(CsrfTokenManager::class),
    $container->get(ViewRenderer::class),
);

$router->get('/login', static fn (Request $request, Container $container): Response =>
    $authController($container)->showLogin()
);
$router->post('/login', static fn (Request $request, Container $container): Response =>
    $authController($container)->login($request)
);
$router->post('/logout', static fn (Request $request, Container $container): Response =>
    $authController($container)->logout($request)
);
