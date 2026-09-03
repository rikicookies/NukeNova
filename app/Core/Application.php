<?php

declare(strict_types=1);

namespace NovaNuke\Core;

use NovaNuke\Core\Config\ConfigLoader;
use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Database\ConnectionFactory;
use NovaNuke\Core\Http\ErrorHandler;
use NovaNuke\Core\Http\Kernel;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\View\ViewRenderer;
use PDO;

final class Application
{
    private function __construct(
        private readonly string $rootPath,
        private readonly Container $container,
    ) {
    }

    public static function create(string $rootPath): self
    {
        $container = new Container();
        $config = (new ConfigLoader($rootPath . '/config'))->load();

        date_default_timezone_set((string) $config->get('app.timezone', 'UTC'));

        $container->instance(self::class, $app = new self($rootPath, $container));
        $container->instance(ConfigRepository::class, $config);
        $container->instance(Router::class, new Router());
        $container->bind(PDO::class, static fn () => (new ConnectionFactory($config))->create());
        $container->bind(ViewRenderer::class, static fn () => new ViewRenderer(
            $rootPath . '/resources/views',
            $rootPath . '/storage/cache/twig',
            (bool) $config->get('app.debug', false),
        ));
        $container->bind(ErrorHandler::class, static fn () => new ErrorHandler(
            (bool) $config->get('app.debug', false),
            $rootPath . '/storage/logs/novanuke.log',
        ));
        $container->bind(Kernel::class, static fn (Container $c) => new Kernel(
            $c,
            $c->get(Router::class),
            $c->get(ErrorHandler::class),
        ));

        $app->loadRoutes();

        return $app;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function kernel(): Kernel
    {
        return $this->container->get(Kernel::class);
    }

    private function loadRoutes(): void
    {
        $router = $this->container->get(Router::class);
        $container = $this->container;
        require $this->rootPath . '/routes/web.php';
    }
}
