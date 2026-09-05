<?php

declare(strict_types=1);

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Version;
use NovaNuke\Core\Modules\ModuleManager;
use NovaNuke\Core\Settings\SettingsRepository;

$router->get('/', static function (Request $request, Container $container): Response {
    $homepage = $container->get(SettingsRepository::class)->string('site.homepage', 'home');
    $targets = ['news' => '/news', 'pages' => '/pages', 'downloads' => '/downloads'];
    if (isset($targets[$homepage])) {
        $module = $container->get(ModuleManager::class)->inventory()[$homepage] ?? null;
        if (($module['enabled'] ?? false) === true) {
            return Response::redirect($targets[$homepage]);
        }
    }

    $html = $container->get(ViewRenderer::class)->render('home.twig', [
        'version' => Version::CURRENT,
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
