<?php

declare(strict_types=1);

namespace Modules\News\src;

use NovaNuke\Core\Admin\AdminMenuBuilding;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\Security\HtmlSanitizer;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use Modules\Comments\src\CommentService;
use Modules\Comments\src\CommentTargetChecking;

final class NewsModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('news', $context->basePath . '/views');
        $context->container->bind(NewsRepository::class, static fn (Container $container) => new NewsRepository($container->get(\PDO::class)));
        $context->container->bind(NewsInput::class, static fn () => new NewsInput(new HtmlSanitizer()));
        $context->container->get(ViewRenderer::class)->addGlobal('news_rss_url', '/news/rss.xml');
    }

    public function boot(ModuleContext $context): void
    {
        $context->events->listen('admin.menu.building', static function (object $event): void {
            if ($event instanceof AdminMenuBuilding) $event->add('News', '/admin/news', 'news.edit');
        });
        $context->events->listen('comments.content.checking', static function (object $event) use ($context): void {
            if ($event instanceof CommentTargetChecking && $event->type === 'news'
                && $context->container->get(NewsRepository::class)->acceptsComments($event->contentId)) {
                $event->accept();
            }
        });
        $public = static fn (Container $container): PublicNewsController => new PublicNewsController(
            $container->get(NewsRepository::class), $container->get(SessionManager::class), $container->get(ViewRenderer::class),
            $container->has(CommentService::class) ? $container->get(CommentService::class) : null,
            $container->has(CommentService::class) ? $container->get(\NovaNuke\Core\Security\CsrfTokenManager::class) : null,
            $container->has(CommentService::class) ? $container->get(\NovaNuke\Auth\AuthManager::class) : null,
        );
        $admin = static fn (Container $container): AdminNewsController => new AdminNewsController(
            $container->get(NewsRepository::class), $container->get(NewsInput::class),
            $container->get(\NovaNuke\Auth\AuthManager::class), $container->get(\NovaNuke\Core\Security\AuthorizationService::class),
            $container->get(\NovaNuke\Core\Logging\ActivityLogger::class), $container->get(\NovaNuke\Core\Events\EventDispatcher::class),
            $container->get(\NovaNuke\Core\Security\CsrfTokenManager::class), $container->get(SessionManager::class),
            $container->get(ViewRenderer::class),
        );
        $rss = static fn (Container $container): RssController => new RssController(
            $container->get(NewsRepository::class), new RssFeedBuilder(),
            $container->get(\NovaNuke\Core\Settings\SettingsRepository::class),
            $container->get(\NovaNuke\Core\Config\ConfigRepository::class),
        );
        $context->router->get('/news', static fn (Request $request, Container $container): Response => $public($container)->index($request), 'news.index');
        $context->router->get('/news/category/{slug}', static fn (Request $request, Container $container): Response => $public($container)->index($request, (string) $request->attribute('slug')), 'news.category');
        $context->router->get('/news/rss.xml', static fn (Request $request, Container $container): Response => $rss($container)->feed(), 'news.rss');
        $context->router->get('/news/{slug}', static fn (Request $request, Container $container): Response => $public($container)->show($request), 'news.show');
        $context->router->get('/admin/news', static fn (Request $request, Container $container): Response => $admin($container)->index());
        $context->router->get('/admin/news/new', static fn (Request $request, Container $container): Response => $admin($container)->create());
        $context->router->get('/admin/news/{id}/edit', static fn (Request $request, Container $container): Response => $admin($container)->edit($request));
        $context->router->post('/admin/news/save', static fn (Request $request, Container $container): Response => $admin($container)->save($request));
        $context->router->post('/admin/news/{id}/delete', static fn (Request $request, Container $container): Response => $admin($container)->delete($request));
        $context->router->post('/admin/news/taxonomy/{type}', static fn (Request $request, Container $container): Response => $admin($container)->taxonomy($request));
    }
}
