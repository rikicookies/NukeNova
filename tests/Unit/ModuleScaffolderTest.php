<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Developer\ModuleScaffolder;
use NovaNuke\Core\Modules\ModuleManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModuleScaffolderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-module-scaffold-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testItCreatesAModuleApiOneScaffold(): void
    {
        $files = (new ModuleScaffolder($this->root))->create('Reading List');
        $path = $this->root . '/ReadingList';

        self::assertContains('module.json', $files);
        self::assertContains('src/ReadingListModule.php', $files);
        self::assertFileExists($path . '/views/index.twig');
        self::assertFileExists($path . '/language/en.json');
        self::assertFileExists($path . '/language/es.json');
        self::assertDirectoryExists($path . '/database/migrations');
        self::assertDirectoryExists($path . '/tests');

        $manifest = ModuleManifest::fromArray(
            json_decode((string) file_get_contents($path . '/module.json'), true, 32, JSON_THROW_ON_ERROR),
            $path,
        );
        self::assertSame('reading-list', $manifest->slug);
        self::assertSame('1.0', $manifest->apiVersion);
        self::assertSame(['reading-list.manage'], $manifest->permissions);
        self::assertSame('Modules\\ReadingList\\src\\ReadingListModule', $manifest->provider);
    }

    public function testItRefusesInvalidNamesAndExistingDirectories(): void
    {
        $scaffolder = new ModuleScaffolder($this->root);
        foreach (['../Bad', '1Bad', 'Bad_Name'] as $name) {
            try {
                $scaffolder->create($name);
                self::fail('Invalid module name should have failed: ' . $name);
            } catch (RuntimeException) {
                self::assertTrue(true);
            }
        }

        $scaffolder->create('Example');
        $this->expectException(RuntimeException::class);
        $scaffolder->create('Example');
    }

    private function remove(string $path): void
    {
        if (! is_dir($path)) return;
        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $target = $path . '/' . $item;
            is_dir($target) ? $this->remove($target) : unlink($target);
        }
        rmdir($path);
    }
}
