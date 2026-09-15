<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Http\Routing\RouteNotFound;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\I18n\Translator;
use NovaNuke\Core\Modules\ModuleMutationScope;
use NovaNuke\Core\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class ModuleMutationIsolationTest extends TestCase
{
    public function testRollbackRemovesOnlyTheFailedOwnersMutations(): void
    {
        $container = new Container();
        $router = new Router();
        $events = new EventDispatcher();
        $container->instance('shared', 'core');

        $container->beginOwner('failed');
        $router->beginOwner('failed');
        ModuleMutationScope::begin('failed');
        $container->instance('shared', 'failed');
        $container->bind('failed.service', static fn (): string => 'failed');
        $router->get('/failed', static fn (): Response => Response::html('failed'));
        $events->listen('probe', static function (object $event): void {$event->failed = true;});
        $container->endOwner();
        $router->endOwner();
        ModuleMutationScope::end();

        $container->beginOwner('healthy');
        $router->beginOwner('healthy');
        ModuleMutationScope::begin('healthy');
        $container->bind('healthy.service', static fn (): string => 'healthy');
        $router->get('/healthy', static fn (): Response => Response::html('healthy'));
        $events->listen('probe', static function (object $event): void {$event->healthy = true;});
        $container->endOwner();
        $router->endOwner();
        ModuleMutationScope::end();

        $container->removeOwner('failed');
        $router->removeOwner('failed');
        ModuleMutationScope::rollback('failed');
        ModuleMutationScope::commit('healthy');

        self::assertSame('core', $container->get('shared'));
        self::assertFalse($container->has('failed.service'));
        self::assertSame('healthy', $container->get('healthy.service'));
        self::assertSame('/healthy', $router->match(Request::create('GET', '/healthy'))->route->path);
        try {
            $router->match(Request::create('GET', '/failed'));
            self::fail('Failed owner route leaked.');
        } catch (RouteNotFound) {
            self::assertTrue(true);
        }
        $payload = $events->dispatch('probe', new \stdClass());
        self::assertFalse(isset($payload->failed));
        self::assertTrue($payload->healthy);
        self::assertSame(1, $events->listenerCount('probe'));
    }

    public function testViewAndTranslationChangesPublishOnlyForSuccessfulOwner(): void
    {
        $root = dirname(__DIR__, 2);
        $temporary = sys_get_temp_dir() . '/novanuke-module-owner-' . bin2hex(random_bytes(6));
        mkdir($temporary . '/failed/views', 0700, true);
        mkdir($temporary . '/failed/language', 0700, true);
        mkdir($temporary . '/healthy/views', 0700, true);
        mkdir($temporary . '/healthy/language', 0700, true);
        file_put_contents($temporary . '/failed/views/index.twig', '{{ failed_global|default("missing") }}');
        file_put_contents($temporary . '/failed/language/en.json', '{"message":"failed"}');
        file_put_contents($temporary . '/healthy/views/index.twig', '{{ healthy_global }}');
        file_put_contents($temporary . '/healthy/language/en.json', '{"message":"healthy"}');
        try {
            $translator = new Translator('en', 'en', $root . '/language');
            $views = new ViewRenderer($root . '/resources/views', $root . '/storage/cache/twig-tests', true, $translator);
            $translator->beginOwner('failed'); $views->beginOwner('failed');
            $translator->addNamespace('failed', $temporary . '/failed/language');
            $views->addNamespace('failed', $temporary . '/failed/views'); $views->addGlobal('failed_global', 'leaked');
            $translator->endOwner(); $views->endOwner();
            $translator->removeOwner('failed'); $views->removeOwner('failed');

            $translator->beginOwner('healthy'); $views->beginOwner('healthy');
            $translator->addNamespace('healthy', $temporary . '/healthy/language');
            $views->addNamespace('healthy', $temporary . '/healthy/views'); $views->addGlobal('healthy_global', 'published');
            $translator->endOwner(); $views->endOwner();
            $translator->commitOwner('healthy'); $views->commitOwner('healthy');

            self::assertSame('failed::message', $translator->translate('failed::message'));
            self::assertSame('healthy', $translator->translate('healthy::message'));
            self::assertSame('published', $views->render('@healthy/index.twig'));
            $this->expectException(\Twig\Error\LoaderError::class);
            $views->render('@failed/index.twig');
        } finally {
            foreach (glob($temporary . '/*/*/*') ?: [] as $file) if (is_file($file)) unlink($file);
            foreach (glob($temporary . '/*/*') ?: [] as $directory) if (is_dir($directory)) rmdir($directory);
            foreach (glob($temporary . '/*') ?: [] as $directory) if (is_dir($directory)) rmdir($directory);
            if (is_dir($temporary)) rmdir($temporary);
        }
    }
}
