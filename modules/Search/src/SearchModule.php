<?php

declare(strict_types=1);

namespace Modules\Search\src;

use NovaNuke\Core\Admin\AdminMenuBuilding;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\View\ViewRenderer;

final class SearchModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('search', $context->basePath . '/views');
        $context->container->bind(SearchRepository::class, static fn (Container $c) => new SearchRepository($c->get(\PDO::class)));
    }

    public function boot(ModuleContext $context): void
    {
        $context->events->listen('admin.menu.building', static function (object $event): void {
            if ($event instanceof AdminMenuBuilding) $event->add('Search', '/admin/search', 'search.manage');
        });
        $public = static fn (Container $c) => new PublicSearchController(
            $c->get(\NovaNuke\Core\Events\EventDispatcher::class), $c->get(SearchRepository::class),
            $c->get(\NovaNuke\Core\Settings\SettingsRepository::class), $c->get(\NovaNuke\Auth\AuthManager::class),
            $c->get(ViewRenderer::class),
        );
        $admin = static fn (Container $c) => new AdminSearchController(
            $c->get(SearchRepository::class), $c->get(\NovaNuke\Core\Settings\SettingsRepository::class),
            $c->get(\NovaNuke\Auth\AuthManager::class), $c->get(\NovaNuke\Core\Security\AuthorizationService::class),
            $c->get(\NovaNuke\Core\Logging\ActivityLogger::class), $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $c->get(\NovaNuke\Core\Security\SessionManager::class), $c->get(ViewRenderer::class),
        );
        $context->router->get('/search', static fn (Request $r, Container $c): Response => $public($c)->index($r), 'search.index');
        $context->router->get('/admin/search', static fn (Request $r, Container $c): Response => $admin($c)->index());
        $context->router->post('/admin/search', static fn (Request $r, Container $c): Response => $admin($c)->update($r));
    }
}
