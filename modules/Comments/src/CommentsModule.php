<?php

declare(strict_types=1);

namespace Modules\Comments\src;

use NovaNuke\Core\Admin\AdminMenuBuilding;
use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\Security\DatabaseRateLimiter;
use NovaNuke\Core\View\ViewRenderer;

final class CommentsModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('comments', $context->basePath . '/views');
        $context->container->bind(CommentRepository::class, static fn (Container $c) => new CommentRepository($c->get(\PDO::class)));
        $context->container->bind(CommentService::class, static fn (Container $c) => new CommentService(
            $c->get(CommentRepository::class), new CommentTreeBuilder(), $c->get(\NovaNuke\Auth\AuthManager::class),
            $c->get(\NovaNuke\Core\Settings\SettingsRepository::class), $c->get(\NovaNuke\Core\Events\EventDispatcher::class),
            new DatabaseRateLimiter($c->get(\PDO::class), 5, 600, 'comments'),
            (string) $c->get(ConfigRepository::class)->get('app.key', ''),
        ));
    }

    public function boot(ModuleContext $context): void
    {
        $context->events->listen('admin.menu.building', static function (object $event): void {
            if ($event instanceof AdminMenuBuilding) $event->add('Comments', '/admin/comments', 'comments.moderate');
        });
        $public = static fn (Container $c) => new PublicCommentsController(
            $c->get(CommentService::class), $c->get(\NovaNuke\Auth\AuthManager::class),
            $c->get(\NovaNuke\Core\Logging\ActivityLogger::class), $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $c->get(\NovaNuke\Core\Security\SessionManager::class),
        );
        $admin = static fn (Container $c) => new AdminCommentsController(
            $c->get(CommentRepository::class), $c->get(\NovaNuke\Core\Settings\SettingsRepository::class),
            $c->get(\NovaNuke\Auth\AuthManager::class), $c->get(\NovaNuke\Core\Security\AuthorizationService::class),
            $c->get(\NovaNuke\Core\Logging\ActivityLogger::class), $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $c->get(ViewRenderer::class), $c->get(\NovaNuke\Core\Security\SessionManager::class),
        );
        $context->router->post('/comments/{id}/report', static fn (Request $r, Container $c): Response => $public($c)->report($r));
        $context->router->post('/comments/{id}/edit', static fn (Request $r, Container $c): Response => $public($c)->edit($r));
        $context->router->post('/comments/{type}/{id}', static fn (Request $r, Container $c): Response => $public($c)->create($r));
        $context->router->get('/admin/comments', static fn (Request $r, Container $c): Response => $admin($c)->index());
        $context->router->post('/admin/comments/{id}/moderate', static fn (Request $r, Container $c): Response => $admin($c)->moderate($r));
        $context->router->post('/admin/comment-reports/{id}/resolve', static fn (Request $r, Container $c): Response => $admin($c)->resolve($r));
        $context->router->post('/admin/comments/settings', static fn (Request $r, Container $c): Response => $admin($c)->settings($r));
    }
}
