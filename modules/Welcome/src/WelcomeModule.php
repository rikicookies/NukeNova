<?php

declare(strict_types=1);

namespace Modules\Welcome\src;

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Modules\ModuleContext;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\View\ViewRenderer;

final class WelcomeModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->container->get(ViewRenderer::class)->addNamespace('welcome', $context->basePath . '/views');
    }

    public function boot(ModuleContext $context): void
    {
        $events = $context->events;
        $context->router->get('/welcome', static function (Request $request, Container $container) use ($events): Response {
            $database = $container->get(\PDO::class);
            $message = $database->query('SELECT message FROM welcome_messages ORDER BY id LIMIT 1')->fetchColumn();
            $event = new WelcomePageEvent(is_string($message) ? $message : 'Welcome to NovaNuke.');
            $events->dispatch('welcome.page.rendering', $event);

            return Response::html($container->get(ViewRenderer::class)->render('@welcome/index.twig', [
                'message' => $event->message,
            ]));
        }, 'welcome.index');
    }
}
