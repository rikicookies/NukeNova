<?php

declare(strict_types=1);

namespace Modules\Pages\src;

use Modules\Comments\src\CommentService;
use Modules\Comments\src\CommentTargetChecking;
use NovaNuke\Core\Admin\AdminMenuBuilding;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\Security\HtmlSanitizer;
use NovaNuke\Core\View\ViewRenderer;

final class PagesModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('pages', $context->basePath . '/views');
        $context->container->bind(PageRepository::class, static fn (Container $c) => new PageRepository($c->get(\PDO::class)));
        $context->container->bind(PageInput::class, static fn () => new PageInput(new HtmlSanitizer()));
    }

    public function boot(ModuleContext $context): void
    {
        $context->events->listen('admin.menu.building', static function (object $event): void {
            if ($event instanceof AdminMenuBuilding) $event->add('Pages', '/admin/pages', 'pages.edit');
        });
        $context->events->listen('comments.content.checking', static function (object $event) use ($context): void {
            if (! ($event instanceof CommentTargetChecking) || $event->type !== 'pages') return;
            $user = $context->container->get(\NovaNuke\Auth\AuthManager::class)->user();
            if ($context->container->get(PageRepository::class)->acceptsComments($event->contentId, $user ? (int) $user['id'] : null)) $event->accept();
        });
        $public = static fn (Container $c) => new PublicPagesController(
            $c->get(PageRepository::class), $c->get(\NovaNuke\Auth\AuthManager::class),
            $c->get(\NovaNuke\Core\Security\SessionManager::class), $c->get(ViewRenderer::class),
            $c->get(\NovaNuke\Core\Events\EventDispatcher::class),
            $c->has(CommentService::class) ? $c->get(CommentService::class) : null,
            $c->has(CommentService::class) ? $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class) : null,
        );
        $admin = static fn (Container $c) => new AdminPagesController(
            $c->get(PageRepository::class), $c->get(PageInput::class), $c->get(\NovaNuke\Auth\AuthManager::class),
            $c->get(\NovaNuke\Core\Security\AuthorizationService::class), $c->get(\NovaNuke\Core\Logging\ActivityLogger::class),
            $c->get(\NovaNuke\Core\Events\EventDispatcher::class), $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $c->get(\NovaNuke\Core\Security\SessionManager::class), $c->get(ViewRenderer::class),
        );
        $context->router->get('/pages', static fn (Request $r, Container $c): Response => $public($c)->index(), 'pages.index');
        $context->router->get('/pages/{slug}', static fn (Request $r, Container $c): Response => $public($c)->show($r), 'pages.show');
        $context->router->get('/admin/pages', static fn (Request $r, Container $c): Response => $admin($c)->index());
        $context->router->get('/admin/pages/new', static fn (Request $r, Container $c): Response => $admin($c)->create());
        $context->router->get('/admin/pages/{id}/edit', static fn (Request $r, Container $c): Response => $admin($c)->edit($r));
        $context->router->post('/admin/pages/save', static fn (Request $r, Container $c): Response => $admin($c)->save($r));
        $context->router->post('/admin/pages/{id}/delete', static fn (Request $r, Container $c): Response => $admin($c)->delete($r));
    }
}
