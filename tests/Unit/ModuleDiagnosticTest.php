<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Developer\ModuleDiagnostic;
use NovaNuke\Core\Developer\ModuleScaffolder;
use PHPUnit\Framework\TestCase;

final class ModuleDiagnosticTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-module-diagnostic-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testGeneratedScaffoldPassesStaticPreflight(): void
    {
        (new ModuleScaffolder($this->root))->create('Reading List');
        $checks = (new ModuleDiagnostic())->inspect($this->root . '/ReadingList', $this->root);

        self::assertNotEmpty($checks);
        foreach ($checks as $check) {
            self::assertTrue($check['passed'], $check['label'] . ': ' . $check['detail']);
        }
        self::assertSame('PASS', $this->byLabel($checks)['Manifest contract']['level']);
        self::assertSame('PASS', $this->byLabel($checks)['Provider']['level']);
        self::assertSame('PASS', $this->byLabel($checks)['Catalogues']['level']);
    }

    public function testBrokenProviderAndMigrationAreFailuresWithoutExecutingModuleCode(): void
    {
        (new ModuleScaffolder($this->root))->create('Example');
        $path = $this->root . '/Example';
        unlink($path . '/src/ExampleModule.php');
        file_put_contents($path . '/database/migrations/not-valid.php', "<?php\nthrow new RuntimeException('must never execute');\n");

        $checks = $this->byLabel((new ModuleDiagnostic())->inspect($path, $this->root));
        self::assertFalse($checks['Provider']['passed']);
        self::assertFalse($checks['Migrations']['passed']);
    }

    public function testMissingDeclaredDependencyFailsCompatibility(): void
    {
        (new ModuleScaffolder($this->root))->create('Example');
        $file = $this->root . '/Example/module.json';
        $data = json_decode((string) file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
        $data['dependencies'] = ['missing-module' => '1.0.0'];
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");

        $checks = $this->byLabel((new ModuleDiagnostic())->inspect($this->root . '/Example', $this->root));
        self::assertFalse($checks['Declared compatibility']['passed']);
        self::assertStringContainsString('Missing dependency', $checks['Declared compatibility']['detail']);
    }

    public function testInvalidCatalogueJsonFailsWithoutBootingTheModule(): void
    {
        (new ModuleScaffolder($this->root))->create('Example');
        file_put_contents($this->root . '/Example/language/es.json', '{broken');
        $checks = $this->byLabel((new ModuleDiagnostic())->inspect($this->root . '/Example', $this->root));

        self::assertFalse($checks['Catalogues']['passed']);
    }


    public function testEveryBundledModuleHasNoPreflightFailures(): void
    {
        $project = dirname(__DIR__, 2);
        $diagnostic = new ModuleDiagnostic();
        foreach (glob($project . '/modules/*/module.json') ?: [] as $manifest) {
            foreach ($diagnostic->inspect(dirname($manifest), $project . '/modules') as $check) {
                self::assertTrue($check['passed'], basename(dirname($manifest)) . ' — ' . $check['label'] . ': ' . $check['detail']);
            }
        }
    }

    public function testNamedRouteOutsideTheModuleNamespaceFails(): void
    {
        (new ModuleScaffolder($this->root))->create('Example');
        $provider = $this->root . '/Example/src/ExampleModule.php';
        $source = (string) file_get_contents($provider);
        $source = str_replace("'example.index'", "'other.index'", $source);
        file_put_contents($provider, $source);

        $checks = $this->byLabel((new ModuleDiagnostic())->inspect($this->root . '/Example', $this->root));
        self::assertFalse($checks['Named routes']['passed']);
        self::assertStringContainsString('other.index', $checks['Named routes']['detail']);
    }

    /** @param list<array{level:string,passed:bool,label:string,detail:string}> $checks
     *  @return array<string,array{level:string,passed:bool,label:string,detail:string}>
     */
    private function byLabel(array $checks): array
    {
        $indexed = [];
        foreach ($checks as $check) $indexed[$check['label']] = $check;
        return $indexed;
    }

    private function remove(string $path): void
    {
        if (! is_dir($path)) return;
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $target = $path . '/' . $item;
            is_dir($target) ? $this->remove($target) : unlink($target);
        }
        rmdir($path);
    }
}
