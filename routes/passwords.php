<?php

declare(strict_types=1);

use NovaNuke\Auth\LoginThrottle;
use NovaNuke\Auth\PasswordPolicy;
use NovaNuke\Auth\PasswordResetController;
use NovaNuke\Auth\PasswordResetService;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;

$passwordController = static fn (Container $container): PasswordResetController => new PasswordResetController(
    $container->get(PasswordResetService::class),
    new LoginThrottle($container->get(SessionManager::class), 3, 900),
    new PasswordPolicy(),
    $container->get(CsrfTokenManager::class),
    $container->get(ViewRenderer::class),
);

$router->get('/forgot-password', static fn (Request $request, Container $container): Response =>
    $passwordController($container)->showForgot()
);
$router->post('/forgot-password', static fn (Request $request, Container $container): Response =>
    $passwordController($container)->send($request)
);
$router->get('/reset-password/{token}', static fn (Request $request, Container $container): Response =>
    $passwordController($container)->showReset($request)
);
$router->post('/reset-password/{token}', static fn (Request $request, Container $container): Response =>
    $passwordController($container)->reset($request)
);
