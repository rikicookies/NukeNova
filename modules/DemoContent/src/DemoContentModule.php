<?php

declare(strict_types=1);

namespace Modules\DemoContent\src;

use NovaNuke\Core\Admin\AdminMenuBuilding;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\View\ViewRenderer;

final class DemoContentModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('demo-content', $context->basePath . '/views');
        $context->container->bind(DemoContentInstaller::class, static fn (Container $container): DemoContentInstaller => new DemoContentInstaller(
            $container->get(\PDO::class),
            $container,
            $container->get(\NovaNuke\Core\Access\EntitlementService::class),
            $container->get(\NovaNuke\Core\Events\EventDispatcher::class),
        ));
    }

    public function boot(ModuleContext $context): void
    {
        $context->events->listen('admin.menu.building', static function (object $event): void {
            if ($event instanceof AdminMenuBuilding) {
                $event->add('Demo content', '/admin/system/demo-content', 'settings.manage', 'system', 'system');
            }
        });
        $controller = static fn (Container $container): DemoContentController => new DemoContentController(
            $container->get(DemoContentInstaller::class),
            $container->get(\NovaNuke\Auth\AuthManager::class),
            $container->get(\NovaNuke\Core\Security\AuthorizationService::class),
            $container->get(\NovaNuke\Core\Logging\ActivityLogger::class),
            $container->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $container->get(\NovaNuke\Core\Security\SessionManager::class),
            $container->get(ViewRenderer::class),
        );
        $context->router->get('/admin/system/demo-content', static fn (Request $request, Container $container): Response => $controller($container)->index());
        $context->router->post('/admin/system/demo-content/install', static fn (Request $request, Container $container): Response => $controller($container)->install($request));
    }
}
