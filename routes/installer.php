<?php

declare(strict_types=1);

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Installer\EnvWriter;
use NovaNuke\Installer\InstallationValidator;
use NovaNuke\Installer\InstallerController;
use NovaNuke\Installer\InstallerService;
use NovaNuke\Installer\RequirementsChecker;
use NovaNuke\Core\I18n\LocaleRegistry;

$controller = static function (Container $container): InstallerController {
    return new InstallerController(
        NOVANUKE_ROOT,
        new RequirementsChecker(),
        new InstallationValidator($container->get(LocaleRegistry::class)),
        new InstallerService(NOVANUKE_ROOT, new EnvWriter()),
        $container->get(CsrfTokenManager::class),
        $container->get(ViewRenderer::class),
    );
};

$router->get('/', static fn (): Response => Response::redirect('/install'));
$router->get('/install', static fn (Request $request, Container $container): Response =>
    $controller($container)->show($request)
);
$router->post('/install', static fn (Request $request, Container $container): Response =>
    $controller($container)->install($request)
);
