<?php

declare(strict_types=1);

namespace Modules\Notifications\src;

use Modules\Comments\src\CommentCreated;
use Modules\PrivateMessages\src\PrivateMessageSent;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Maintenance\MaintenancePruning;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\View\ViewRenderer;
use Throwable;

final class NotificationsModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('notifications', $context->basePath . '/views');
        $context->container->bind(NotificationRepository::class, static fn (Container $c) => new NotificationRepository($c->get(\PDO::class)));
        $context->container->bind(NotificationPublisher::class, static fn (Container $c) => new NotificationPublisher($c->get(NotificationRepository::class)));
    }

    public function boot(ModuleContext $context): void
    {
        $repository = $context->container->get(NotificationRepository::class);
        $publisher = $context->container->get(NotificationPublisher::class);
        $user = $context->container->get(\NovaNuke\Auth\AuthManager::class)->user();
        $context->container->get(ViewRenderer::class)->addGlobal(
            'notification_unread_count',
            $user === null ? 0 : $repository->unreadCount((int) $user['id']),
        );

        $context->events->listen('private-message.sent', static function (object $event) use ($publisher): void {
            if (! $event instanceof PrivateMessageSent) return;
            try {
                $publisher->toUser(
                    $event->recipientId,
                    'private-message.received',
                    'New private message',
                    'You received a new private message.',
                    '/messages/' . $event->conversationId,
                    'private-message:' . $event->messageKey,
                );
            } catch (Throwable $error) {
                error_log('Notification delivery failed: ' . $error->getMessage());
            }
        });
        $context->events->listen('comment.created', static function (object $event) use ($publisher): void {
            if (! $event instanceof CommentCreated || $event->status !== 'pending') return;
            try {
                $publisher->toPermission(
                    'comments.moderate',
                    'comment.pending',
                    'Comment awaiting moderation',
                    'A new comment requires review.',
                    '/admin/comments',
                    'comment-pending:' . $event->id,
                );
            } catch (Throwable $error) {
                error_log('Notification delivery failed: ' . $error->getMessage());
            }
        });
        $context->events->listen('maintenance.pruning', static function (object $event) use ($repository): void {
            if ($event instanceof MaintenancePruning) $event->add('notifications.read', $repository->prune($event->dryRun));
        });

        $controller = static fn (Container $c) => new PublicNotificationsController(
            $c->get(NotificationRepository::class),
            $c->get(\NovaNuke\Auth\AuthManager::class),
            $c->get(\NovaNuke\Core\Security\CsrfTokenManager::class),
            $c->get(\NovaNuke\Core\Security\SessionManager::class),
            $c->get(ViewRenderer::class),
        );
        $context->router->get('/notifications', static fn (Request $r, Container $c): Response => $controller($c)->index(), 'notifications.index');
        $context->router->post('/notifications/read-all', static fn (Request $r, Container $c): Response => $controller($c)->readAll($r));
        $context->router->post('/notifications/{id}/read', static fn (Request $r, Container $c): Response => $controller($c)->read($r));
    }
}
