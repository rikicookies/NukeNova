<?php

declare(strict_types=1);

namespace Modules\Downloads\src;

use NovaNuke\Core\Admin\AdminMenuBuilding;
use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\Security\DatabaseRateLimiter;
use NovaNuke\Core\Security\HtmlSanitizer;
use NovaNuke\Core\View\ViewRenderer;
use Modules\Search\src\SearchProvidersRegistering;

final class DownloadsModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('downloads', $context->basePath . '/views');
        $context->container->bind(DownloadRepository::class, static fn (Container $c) => new DownloadRepository($c->get(\PDO::class)));
        $context->container->bind(DownloadInput::class, static fn () => new DownloadInput(new HtmlSanitizer()));
        $context->container->bind(DownloadManager::class, static fn (Container $c) => new DownloadManager(
            $c->get(DownloadRepository::class), new DownloadUploadValidator(), new DownloadStorage(NOVANUKE_ROOT . '/storage/private/downloads'),
            $c->get(\NovaNuke\Auth\AuthManager::class), $c->get(\NovaNuke\Core\Events\EventDispatcher::class),
            new DatabaseRateLimiter($c->get(\PDO::class), 5, 600, 'download-reports'), (string) $c->get(ConfigRepository::class)->get('app.key', ''),
        ));
    }

    public function boot(ModuleContext $context): void
    {
        $context->events->listen('search.providers.registering', static function (object $event) use ($context): void {
            if ($event instanceof SearchProvidersRegistering) $event->registry->add(new DownloadsSearchProvider($context->container->get(\PDO::class)));
        });
        $context->events->listen('admin.menu.building', static function (object $event): void { if ($event instanceof AdminMenuBuilding) $event->add('Downloads', '/admin/downloads', 'downloads.manage'); });
        $public = static fn (Container $c) => new PublicDownloadsController(
            $c->get(DownloadRepository::class), $c->get(DownloadManager::class), $c->get(\NovaNuke\Auth\AuthManager::class),
            $c->get(\NovaNuke\Core\Events\EventDispatcher::class), $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $c->get(\NovaNuke\Core\Security\SessionManager::class), $c->get(ViewRenderer::class),
        );
        $admin = static fn (Container $c) => new AdminDownloadsController(
            $c->get(DownloadRepository::class), $c->get(DownloadManager::class), $c->get(DownloadInput::class),
            $c->get(\NovaNuke\Auth\AuthManager::class), $c->get(\NovaNuke\Core\Security\AuthorizationService::class),
            $c->get(\NovaNuke\Core\Logging\ActivityLogger::class), $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $c->get(\NovaNuke\Core\Security\SessionManager::class), $c->get(ViewRenderer::class),
        );
        $context->router->get('/downloads', static fn (Request $r, Container $c): Response => $public($c)->index($r), 'downloads.index');
        $context->router->get('/downloads/category/{slug}', static fn (Request $r, Container $c): Response => $public($c)->index($r, (string) $r->attribute('slug')), 'downloads.category');
        $context->router->get('/downloads/{slug}/get', static fn (Request $r, Container $c): Response => $public($c)->deliver($r), 'downloads.deliver');
        $context->router->post('/downloads/{id}/report', static fn (Request $r, Container $c): Response => $public($c)->report($r));
        $context->router->get('/downloads/{slug}', static fn (Request $r, Container $c): Response => $public($c)->show($r), 'downloads.show');
        $context->router->get('/admin/downloads', static fn (Request $r, Container $c): Response => $admin($c)->index());
        $context->router->get('/admin/downloads/new', static fn (Request $r, Container $c): Response => $admin($c)->create());
        $context->router->get('/admin/downloads/{id}/edit', static fn (Request $r, Container $c): Response => $admin($c)->edit($r));
        $context->router->post('/admin/downloads/save', static fn (Request $r, Container $c): Response => $admin($c)->save($r));
        $context->router->post('/admin/downloads/category', static fn (Request $r, Container $c): Response => $admin($c)->category($r));
        $context->router->post('/admin/downloads/{id}/delete', static fn (Request $r, Container $c): Response => $admin($c)->delete($r));
        $context->router->post('/admin/download-reports/{id}/resolve', static fn (Request $r, Container $c): Response => $admin($c)->resolve($r));
    }
}
