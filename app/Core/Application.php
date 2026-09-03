<?php

declare(strict_types=1);

namespace NovaNuke\Core;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Auth\LoginThrottle;
use NovaNuke\Core\Config\ConfigLoader;
use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Database\ConnectionFactory;
use NovaNuke\Core\Http\ErrorHandler;
use NovaNuke\Core\Http\Kernel;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
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
        $container->bind(SessionManager::class, static function () use ($config): SessionManager {
            $session = new SessionManager(
                (string) $config->get('session.name', 'novanuke_session'),
                (bool) $config->get('session.secure', false),
                (string) $config->get('session.same_site', 'Lax'),
                (int) $config->get('session.lifetime', 7200),
            );
            $session->start();

            return $session;
        });
        $container->bind(CsrfTokenManager::class, static fn (Container $c) => new CsrfTokenManager(
            $c->get(SessionManager::class),
        ));
        $container->bind(PDO::class, static fn () => (new ConnectionFactory($config))->create());
        $container->bind(AuthManager::class, static fn (Container $c) => new AuthManager(
            $c->get(PDO::class),
            $c->get(SessionManager::class),
        ));
        $container->bind(LoginThrottle::class, static fn (Container $c) => new LoginThrottle(
            $c->get(SessionManager::class),
        ));
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
        $installed = is_file($this->rootPath . '/storage/installed.lock');

        if (! $installed) {
            require $this->rootPath . '/routes/installer.php';
            return;
        }

        require $this->rootPath . '/routes/web.php';
        require $this->rootPath . '/routes/auth.php';
        require $this->rootPath . '/routes/admin.php';
    }
}
