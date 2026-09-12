<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\ModuleApi;
use NovaNuke\Core\Modules\ModuleInterface;
use NovaNuke\Core\Modules\ModuleManifest;
use PHPUnit\Framework\TestCase;

final class BundledModuleContractTest extends TestCase
{
    public function testEveryBundledManifestSatisfiesThePublicModuleContract(): void
    {
        $root = dirname(__DIR__, 2);
        $files = glob($root . '/modules/*/module.json') ?: [];
        sort($files, SORT_STRING);
        self::assertNotEmpty($files);

        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($data, $file);
            $manifest = ModuleManifest::fromArray($data, dirname($file));
            self::assertSame(ModuleApi::VERSION, $manifest->apiVersion, $manifest->slug);
            self::assertTrue(class_exists($manifest->provider), $manifest->provider);
            self::assertTrue(is_subclass_of($manifest->provider, ModuleInterface::class), $manifest->provider);
        }
    }

    public function testBundledManifestPermissionsStayOwnedByTheirModule(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (glob($root . '/modules/*/module.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
            $slug = (string) ($data['slug'] ?? '');
            foreach (($data['permissions'] ?? []) as $permission) {
                self::assertStringStartsWith($slug . '.', (string) $permission, basename(dirname($file)));
            }
        }
    }
}
