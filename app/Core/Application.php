<?php

declare(strict_types=1);

namespace NovaNuke\Core;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Auth\PasswordResetService;
use NovaNuke\Auth\RegistrationService;
use NovaNuke\Auth\ProfileRepository;
use NovaNuke\Auth\AvatarStorage;
use NovaNuke\Auth\AccountPasswordService;
use NovaNuke\Auth\AccountLifecycleService;
use NovaNuke\Auth\AccountSecurityRepository;
use NovaNuke\Auth\AccountEmailService;
use NovaNuke\Auth\LoginHistoryPresenter;
use NovaNuke\Auth\PasswordPolicy;
use NovaNuke\Core\Config\ConfigLoader;
use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Database\ConnectionFactory;
use NovaNuke\Core\Database\MigrationStatus;
use NovaNuke\Core\Database\Migrator;
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
use NovaNuke\Core\Blocks\MarkdownRenderer;
use NovaNuke\Core\Security\HtmlSanitizer;
use NovaNuke\Core\Menus\MenuManager;
use NovaNuke\Core\Menus\MenuRepository;
use NovaNuke\Core\Menus\MenuTreeBuilder;
use NovaNuke\Core\Menus\MenuUrlResolver;
use NovaNuke\Core\System\SystemInspector;
use NovaNuke\Core\Backup\DatabaseBackup;
use NovaNuke\Core\Backup\FileBackup;
use NovaNuke\Core\System\MaintenanceMode;
use NovaNuke\Core\Security\AuthorizationAudit;
use NovaNuke\Core\Security\AdminAccessGate;
use NovaNuke\Core\Cache\CacheManager;
use NovaNuke\Core\System\ReleaseChecklist;
use NovaNuke\Core\System\ProductionReadiness;
use NovaNuke\Core\Admin\AdminDashboardService;
use NovaNuke\Core\I18n\Translator;
use NovaNuke\Core\I18n\LocaleRegistry;
use NovaNuke\Core\Maintenance\DataPruner;
use PDO;

final class Application
{
    public const VERSION = Version::CURRENT;

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
        $container->instance(LocaleRegistry::class, new LocaleRegistry($rootPath . '/language'));
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
        $container->bind(Translator::class, static fn () => new Translator(
            (string) $config->get('app.locale', 'en'),
            (string) $config->get('app.fallback_locale', 'en'),
            $rootPath . '/language',
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
            $c->get(SettingsRepository::class)->string('site.url', (string) $config->get('app.url', 'http://localhost')),
        ));
        $container->bind(RegistrationService::class, static fn (Container $c) => new RegistrationService(
            $c->get(PDO::class),
            $c->get(SettingsRepository::class),
            $c->get(Mailer::class),
            $c->get(EventDispatcher::class),
            $c->get(SettingsRepository::class)->string('site.url', (string) $config->get('app.url', 'http://localhost')),
        ));
        $container->bind(ProfileRepository::class, static fn (Container $c) => new ProfileRepository($c->get(PDO::class)));
        $container->bind(AvatarStorage::class, static fn () => new AvatarStorage($rootPath . '/storage/private/avatars'));
        $container->bind(AccountPasswordService::class, static fn (Container $c) => new AccountPasswordService(
            $c->get(PDO::class), new PasswordPolicy(),
        ));
        $container->bind(AccountSecurityRepository::class, static fn (Container $c) => new AccountSecurityRepository(
            $c->get(PDO::class), new LoginHistoryPresenter(),
        ));
        $container->bind(AccountLifecycleService::class, static fn (Container $c) => new AccountLifecycleService(
            $c->get(PDO::class), $c->get(EventDispatcher::class),
        ));
        $container->bind(AccountEmailService::class, static fn (Container $c) => new AccountEmailService(
            $c->get(PDO::class), $c->get(Mailer::class), $c->get(EventDispatcher::class),
            $c->get(SettingsRepository::class)->string('site.url', (string) $config->get('app.url', 'http://localhost')),
        ));
        $container->bind(AuthManager::class, static fn (Container $c) => new AuthManager(
            $c->get(PDO::class),
            $c->get(SessionManager::class),
            $c->get(EventDispatcher::class),
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
            $c->get(Translator::class),
        ));
        $container->bind(MigrationStatus::class, static fn (Container $c) => new MigrationStatus(
            new Migrator($c->get(PDO::class)),
            new ModuleMigrator($c->get(PDO::class)),
            $c->get(ModuleManager::class),
            $rootPath . '/database/migrations',
        ));
        $container->bind(ThemeManager::class, static fn (Container $c) => new ThemeManager(
            new ThemeDetector($rootPath . '/themes'),
            new ThemeRepository($c->get(PDO::class)),
            new ThemeAssetPublisher($rootPath . '/public/assets/themes'),
            $c->get(SettingsRepository::class),
            $c->get(ViewRenderer::class),
            $c->get(EventDispatcher::class),
            $c->get(Translator::class),
            self::VERSION,
        ));
        $container->bind(BlockManager::class, static fn (Container $c) => new BlockManager(
            $c->get(PDO::class),
            new BlockRepository($c->get(PDO::class)),
            new HtmlSanitizer(),
            new MarkdownRenderer(new HtmlSanitizer()),
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
        $container->bind(ViewRenderer::class, static fn (Container $c) => new ViewRenderer(
            $rootPath . '/resources/views',
            $rootPath . '/storage/cache/twig',
            (bool) $config->get('app.debug', false),
            $c->get(Translator::class),
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
            $c->get(SettingsRepository::class),
            $c->get(MigrationStatus::class),
        ));
        $container->bind(AuthorizationAudit::class, static fn (Container $c) => new AuthorizationAudit(
            $c->get(PDO::class),
        ));
        $container->bind(AdminAccessGate::class, static fn () => new AdminAccessGate());
        $container->bind(DatabaseBackup::class, static fn (Container $c) => new DatabaseBackup(
            $c->get(PDO::class),
            $rootPath . '/storage/private/backups',
        ));
        $container->bind(FileBackup::class, static fn () => new FileBackup(
            $rootPath,
            $rootPath . '/storage/private/backups',
        ));
        $container->bind(CacheManager::class, static fn () => new CacheManager(
            $rootPath . '/storage/cache',
        ));
        $container->bind(ReleaseChecklist::class, static fn () => new ReleaseChecklist($rootPath));
        $container->bind(ProductionReadiness::class, static fn (Container $c) => new ProductionReadiness($c->get(ConfigRepository::class), $rootPath));
        $container->bind(DataPruner::class, static fn (Container $c) => new DataPruner(
            $c->get(PDO::class), $c->get(EventDispatcher::class),
        ));
        $container->bind(AdminDashboardService::class, static fn (Container $c) => new AdminDashboardService(
            $c->get(PDO::class),
            $c->get(ModuleManager::class),
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
            $c->get(AdminAccessGate::class),
        ));

        $container->get(ViewRenderer::class)->addGlobal('cms_version', self::VERSION);
        $container->get(ViewRenderer::class)->addGlobal('cms_locales', $container->get(LocaleRegistry::class)->all());

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
        require $this->rootPath . '/routes/account.php';
        require $this->rootPath . '/routes/admin.php';
        $settings = $this->container->get(SettingsRepository::class);
        $views = $this->container->get(ViewRenderer::class);
        $timezone = $settings->string('site.timezone', (string) $this->container->get(ConfigRepository::class)->get('app.timezone', 'UTC'));
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'UTC';
        }
        $locale = $settings->string('site.locale', 'en');
        $locales = $this->container->get(LocaleRegistry::class);
        $locale = $locales->fallback($locale);
        $authenticatedUser = $this->container->get(AuthManager::class)->user();
        if ($authenticatedUser !== null) {
            $profile = $this->container->get(ProfileRepository::class)->byUserId((int) $authenticatedUser['id']);
            if ($profile !== null && $locales->supports((string) $profile['locale'])) $locale = (string) $profile['locale'];
            if ($profile !== null && in_array($profile['timezone'], timezone_identifiers_list(), true)) $timezone = $profile['timezone'];
        }
        $this->container->get(Translator::class)->setLocale($locale);
        $dateFormat = $settings->string('site.date_format', 'F j, Y');
        if (! isset(\NovaNuke\Core\Settings\GeneralSettingsInput::DATE_FORMATS[$dateFormat])) {
            $dateFormat = 'F j, Y';
        }
        date_default_timezone_set($timezone);
        $views->addGlobal('cms_name', $settings->string('site.name', 'NovaNuke'));
        $views->addGlobal('cms_url', $settings->string('site.url', ''));
        $views->addGlobal('cms_locale', $locale);
        $views->addGlobal('cms_description', $settings->string('site.description', 'A modern modular CMS with an old-school spirit.'));
        $views->addGlobal('cms_admin_email', $settings->string('site.admin_email', ''));
        $views->addGlobal('cms_timezone', $timezone);
        $views->addGlobal('cms_date_format', $dateFormat);
        $views->addGlobal('cms_per_page', $settings->integer('site.per_page', 10, 5, 100));
        $views->addGlobal('current_user', $authenticatedUser);
        $this->container->get(ThemeManager::class)->bootActive();
        $this->container->get(ModuleManager::class)->bootEnabled();
        $this->container->get(BlockManager::class)->boot();
        $this->container->get(MenuManager::class)->boot();
    }
}
