<?php

declare(strict_types=1);

namespace NovaNuke\Core;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Auth\PasswordResetService;
use NovaNuke\Auth\RegistrationService;
use NovaNuke\Core\Config\ConfigLoader;
use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Database\ConnectionFactory;
use NovaNuke\Core\Http\ErrorHandler;
use NovaNuke\Core\Http\Kernel;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\Mail\LogMailer;
use NovaNuke\Core\Mail\Mailer;
use NovaNuke\Core\Mail\SmtpConfiguration;
use NovaNuke\Core\Mail\SmtpMailer;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\DatabaseRateLimiter;
use NovaNuke\Core\Security\RateLimiter;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\Security\SecurityHeaders;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Modules\ModuleCompatibilityChecker;
use NovaNuke\Core\Modules\ModuleDetector;
use NovaNuke\Core\Modules\ModuleManager;
use NovaNuke\Core\Modules\ModuleMigrator;
use NovaNuke\Core\Modules\ModuleRepository;
use NovaNuke\Core\Themes\ThemeAssetPublisher;
use NovaNuke\Core\Themes\ThemeDetector;
use NovaNuke\Core\Themes\ThemeManager;
use NovaNuke\Core\Themes\ThemeRepository;
use NovaNuke\Core\Blocks\BlockManager;
use NovaNuke\Core\Blocks\BlockRepository;
use NovaNuke\Core\Blocks\BlockVisibility;
use NovaNuke\Core\Security\HtmlSanitizer;
use NovaNuke\Core\Menus\MenuManager;
use NovaNuke\Core\Menus\MenuRepository;
use NovaNuke\Core\Menus\MenuTreeBuilder;
use NovaNuke\Core\Menus\MenuUrlResolver;
use NovaNuke\Core\System\SystemInspector;
use NovaNuke\Core\Backup\DatabaseBackup;
use NovaNuke\Core\System\MaintenanceMode;
use NovaNuke\Core\Security\AuthorizationAudit;
use NovaNuke\Core\Cache\CacheManager;
use PDO;

final class Application
{
    public const VERSION = '0.1.0';

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
        $container->instance(EventDispatcher::class, new EventDispatcher());
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
        $container->bind(SettingsRepository::class, static fn (Container $c) => new SettingsRepository(
            $c->get(PDO::class),
        ));
        $container->bind(Mailer::class, static function () use ($config): Mailer {
            $mailer = (string) $config->get('mail.mailer', 'log');
            if ($mailer === 'log') {
                return new LogMailer(
                    (string) $config->require('mail.log_path'),
                    (string) $config->get('app.environment', 'production'),
                    (string) $config->get('mail.from_address', 'noreply@localhost'),
                    (string) $config->get('mail.from_name', 'NovaNuke'),
                );
            }
            if ($mailer === 'smtp') {
                return new SmtpMailer(new SmtpConfiguration(
                    (string) $config->get('mail.host', ''),
                    (int) $config->get('mail.port', 465),
                    (string) $config->get('mail.username', ''),
                    (string) $config->get('mail.password', ''),
                    strtolower((string) $config->get('mail.encryption', 'ssl')),
                    (int) $config->get('mail.timeout', 15),
                    (string) $config->get('mail.from_address', ''),
                    (string) $config->get('mail.from_name', 'NovaNuke'),
                ));
            }
            throw new \RuntimeException("Unsupported mailer: {$mailer}");
        });
        $container->bind(PasswordResetService::class, static fn (Container $c) => new PasswordResetService(
            $c->get(PDO::class),
            $c->get(Mailer::class),
            (string) $config->get('app.url', 'http://localhost'),
        ));
        $container->bind(RegistrationService::class, static fn (Container $c) => new RegistrationService(
            $c->get(PDO::class),
            $c->get(SettingsRepository::class),
            $c->get(Mailer::class),
            (string) $config->get('app.url', 'http://localhost'),
        ));
        $container->bind(AuthManager::class, static fn (Container $c) => new AuthManager(
            $c->get(PDO::class),
            $c->get(SessionManager::class),
        ));
        $container->bind(RateLimiter::class, static fn (Container $c) => new DatabaseRateLimiter(
            $c->get(PDO::class),
            5,
            300,
            'login',
        ));
        $container->bind(AuthorizationService::class, static fn (Container $c) => new AuthorizationService(
            $c->get(PDO::class),
        ));
        $container->bind(ActivityLogger::class, static fn (Container $c) => new ActivityLogger(
            $c->get(PDO::class),
        ));
        $container->bind(ModuleManager::class, static fn (Container $c) => new ModuleManager(
            $c->get(PDO::class),
            new ModuleDetector($rootPath . '/modules'),
            new ModuleRepository($c->get(PDO::class)),
            new ModuleMigrator($c->get(PDO::class)),
            new ModuleCompatibilityChecker(self::VERSION),
            $c,
            $c->get(Router::class),
            $c->get(EventDispatcher::class),
        ));
        $container->bind(ThemeManager::class, static fn (Container $c) => new ThemeManager(
            new ThemeDetector($rootPath . '/themes'),
            new ThemeRepository($c->get(PDO::class)),
            new ThemeAssetPublisher($rootPath . '/public/assets/themes'),
            $c->get(SettingsRepository::class),
            $c->get(ViewRenderer::class),
            $c->get(EventDispatcher::class),
            self::VERSION,
        ));
        $container->bind(BlockManager::class, static fn (Container $c) => new BlockManager(
            $c->get(PDO::class),
            new BlockRepository($c->get(PDO::class)),
            new HtmlSanitizer(),
            new BlockVisibility(),
            $c->get(AuthManager::class),
            $c->get(ViewRenderer::class),
            $c->get(EventDispatcher::class),
        ));
        $container->bind(MenuManager::class, static fn (Container $c) => new MenuManager(
            $c->get(PDO::class),
            new MenuRepository($c->get(PDO::class)),
            new MenuUrlResolver(),
            new MenuTreeBuilder(),
            $c->get(AuthManager::class),
            $c->get(ViewRenderer::class),
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
        $container->bind(SecurityHeaders::class, static fn () => new SecurityHeaders(
            (bool) $config->get('security.headers_enabled', true),
            (bool) $config->get('security.hsts_enabled', false),
            (int) $config->get('security.hsts_max_age', 31536000),
            (string) $config->get('app.url', 'http://localhost'),
            (string) $config->get('app.environment', 'production'),
        ));
        $container->bind(SystemInspector::class, static fn (Container $c) => new SystemInspector(
            $config,
            $c->get(ModuleManager::class),
            $rootPath,
            $c->get(AuthorizationAudit::class),
        ));
        $container->bind(AuthorizationAudit::class, static fn (Container $c) => new AuthorizationAudit(
            $c->get(PDO::class),
        ));
        $container->bind(DatabaseBackup::class, static fn (Container $c) => new DatabaseBackup(
            $c->get(PDO::class),
            $rootPath . '/storage/private/backups',
        ));
        $container->bind(CacheManager::class, static fn () => new CacheManager(
            $rootPath . '/storage/cache',
        ));
        $container->bind(MaintenanceMode::class, static fn (Container $c) => new MaintenanceMode(
            $c->get(SettingsRepository::class),
            $c->get(AuthManager::class),
            is_file($rootPath . '/storage/installed.lock'),
        ));
        $container->bind(Kernel::class, static fn (Container $c) => new Kernel(
            $c,
            $c->get(Router::class),
            $c->get(ErrorHandler::class),
            $c->get(SecurityHeaders::class),
            $c->get(MaintenanceMode::class),
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
        require $this->rootPath . '/routes/passwords.php';
        require $this->rootPath . '/routes/registration.php';
        require $this->rootPath . '/routes/admin.php';
        $settings = $this->container->get(SettingsRepository::class);
        $views = $this->container->get(ViewRenderer::class);
        $views->addGlobal('cms_name', $settings->string('site.name', 'NovaNuke'));
        $views->addGlobal('cms_url', $settings->string('site.url', ''));
        $views->addGlobal('cms_locale', $settings->string('site.locale', 'en'));
        $this->container->get(ThemeManager::class)->bootActive();
        $this->container->get(ModuleManager::class)->bootEnabled();
        $this->container->get(BlockManager::class)->boot();
        $this->container->get(MenuManager::class)->boot();
    }
}
