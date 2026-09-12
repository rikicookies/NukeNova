<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\ModuleApi;
use NovaNuke\Core\Modules\ModuleManifest;
use PHPUnit\Framework\TestCase;

final class QuotesReferenceModuleTest extends TestCase
{
    public function testReferenceModuleDemonstratesTheExpectedApiSurface(): void
    {
        $root = dirname(__DIR__, 2);
        $module = $root . '/modules/Quotes';
        $manifest = ModuleManifest::fromArray(
            json_decode((string) file_get_contents($module . '/module.json'), true, 32, JSON_THROW_ON_ERROR),
            $module,
        );

        self::assertSame('quotes', $manifest->slug);
        self::assertSame(ModuleApi::VERSION, $manifest->apiVersion);
        self::assertContains('quotes.manage', $manifest->permissions);
        self::assertFileExists($module . '/database/migrations/2026_09_10_000001_create_quotes_table.php');
        self::assertFileExists($module . '/views/index.twig');
        self::assertFileExists($module . '/views/admin/index.twig');
        self::assertFileExists($module . '/language/en.json');
        self::assertFileExists($module . '/language/es.json');
    }

    public function testReferenceProviderUsesNamespacedRoutesAndCoreAdminHook(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Quotes/src/QuotesModule.php');
        self::assertStringContainsString('EventName::ADMIN_MENU_BUILDING', $source);
        self::assertStringContainsString("'quotes.manage'", $source);
        foreach (['quotes.index', 'quotes.admin', 'quotes.create', 'quotes.delete'] as $route) {
            self::assertStringContainsString("'{$route}'", $source);
        }
        self::assertStringNotContainsString('Modules\\Comments\\', $source);
        self::assertStringNotContainsString('Modules\\Search\\', $source);
    }

    public function testReferenceAdminWritesAreProtectedByCsrfAndAuthorization(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Quotes/src/QuotesController.php');
        self::assertStringContainsString("'quotes.manage'", $source);
        self::assertStringContainsString('$this->csrf->validate', $source);
        self::assertStringContainsString('ActivityLogger', $source);
        self::assertStringContainsString("Response::redirect('/admin/quotes', 303)", $source);
    }

    public function testCliAdvertisesSafeModuleGeneration(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        self::assertStringContainsString("if (\$command === 'module:make')", $cli);
        self::assertStringContainsString('ModuleScaffolder', $cli);
        self::assertStringContainsString('module:make NAME', $cli);
    }
}
