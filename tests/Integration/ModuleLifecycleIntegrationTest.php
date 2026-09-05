<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\I18n\Translator;
use NovaNuke\Core\Modules\ModuleCompatibilityChecker;
use NovaNuke\Core\Modules\ModuleDetector;
use NovaNuke\Core\Modules\ModuleManager;
use NovaNuke\Core\Modules\ModuleMigrator;
use NovaNuke\Core\Modules\ModuleRepository;
use NovaNuke\Core\Version;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use PDO;

final class ModuleLifecycleIntegrationTest extends MySqlIntegrationTestCase
{
    public function testInstallEnableBootDisableAndControlledUninstallPreserveData(): void
    {
        $manager = $this->manager();
        $manager->install('welcome');

        self::assertSame(1, (int) $this->db()->query("SELECT COUNT(*) FROM modules WHERE slug='welcome'")->fetchColumn());
        self::assertSame(1, (int) $this->db()->query("SELECT COUNT(*) FROM module_migrations WHERE module_slug='welcome'")->fetchColumn());
        self::assertSame(1, (int) $this->db()->query("SELECT COUNT(*) FROM permissions WHERE slug='welcome.view'")->fetchColumn());

        $manager->enable('welcome');
        $manager->bootEnabled();
        $match = $this->router()->match(Request::create('GET', '/welcome'));
        self::assertSame('welcome.index', $match->route->name);

        $manager->disable('welcome');
        self::assertSame(0, (int) $this->db()->query("SELECT enabled FROM modules WHERE slug='welcome'")->fetchColumn());
        $manager->uninstall('welcome', false);
        self::assertSame(0, (int) $this->db()->query("SELECT COUNT(*) FROM modules WHERE slug='welcome'")->fetchColumn());
        self::assertSame(1, (int) $this->db()->query('SELECT COUNT(*) FROM welcome_messages')->fetchColumn());
        self::assertSame(1, (int) $this->db()->query("SELECT COUNT(*) FROM module_migrations WHERE module_slug='welcome'")->fetchColumn());
    }

    public function testDestructiveUninstallDropsOnlyTheModuleOwnedSchema(): void
    {
        $manager = $this->manager();
        $manager->install('welcome');
        $manager->enable('welcome');
        $manager->uninstall('welcome', true);

        self::assertSame(0, (int) $this->db()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='welcome_messages'"
        )->fetchColumn());
        self::assertSame(1, (int) $this->db()->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='users'"
        )->fetchColumn());
    }

    private ?Router $testRouter = null;

    private function router(): Router
    {
        return $this->testRouter ?? throw new \LogicException('Router has not been initialized.');
    }

    private function manager(): ModuleManager
    {
        $root = dirname(__DIR__, 2);
        $container = new Container();
        $this->testRouter = new Router();
        $events = new EventDispatcher();
        $translator = new Translator('en', 'en', $root . '/language');
        $views = new ViewRenderer($root . '/resources/views', $root . '/storage/cache/twig-tests', true, $translator);
        $container->instance(PDO::class, $this->db());
        $container->instance(ViewRenderer::class, $views);

        return new ModuleManager(
            $this->db(),
            new ModuleDetector($root . '/modules'),
            new ModuleRepository($this->db()),
            new ModuleMigrator($this->db()),
            new ModuleCompatibilityChecker(Version::CURRENT),
            $container,
            $this->testRouter,
            $events,
            $translator,
        );
    }
}
