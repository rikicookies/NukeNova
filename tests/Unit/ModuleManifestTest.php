<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Modules\ModuleManifest;
use PHPUnit\Framework\TestCase;

final class ModuleManifestTest extends TestCase
{
    public function testItBuildsAValidManifest(): void
    {
        $manifest = ModuleManifest::fromArray($this->valid(), '/modules/Example');

        self::assertSame('example', $manifest->slug);
        self::assertSame('1.2.3', $manifest->version);
        self::assertSame(['welcome' => '1.0.0'], $manifest->dependencies);
        self::assertSame(['example.created'], $manifest->events);
        self::assertSame('1.0', $manifest->apiVersion);
    }

    public function testItRejectsUnsafeSlugs(): void
    {
        $data = $this->valid();
        $data['slug'] = '../example';
        $this->expectException(InvalidArgumentException::class);

        ModuleManifest::fromArray($data, '/modules/Example');
    }

    public function testItRejectsSelfDependencies(): void
    {
        $data = $this->valid();
        $data['dependencies'] = ['example' => '1.0.0'];
        $this->expectException(InvalidArgumentException::class);

        ModuleManifest::fromArray($data, '/modules/Example');
    }

    public function testItRejectsUnsafeEventNames(): void
    {
        $data = $this->valid();
        $data['events'] = ['../unsafe'];
        $this->expectException(InvalidArgumentException::class);

        ModuleManifest::fromArray($data, '/modules/Example');
    }

    public function testItRejectsInvalidApiVersions(): void
    {
        $data = $this->valid(); $data['api_version'] = '../1.0';
        $this->expectException(InvalidArgumentException::class);
        ModuleManifest::fromArray($data, '/modules/Example');
    }


    public function testItRejectsLooseSemanticVersions(): void
    {
        foreach (['version' => '1.2.3garbage', 'cms_min_version' => '0.2', 'php_min_version' => '8.3.x'] as $field => $value) {
            $data = $this->valid();
            $data[$field] = $value;
            try {
                ModuleManifest::fromArray($data, '/modules/Example');
                self::fail("Expected {$field} to be rejected.");
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }

        $data = $this->valid();
        $data['dependencies'] = ['welcome' => '1.0.0junk'];
        $this->expectException(InvalidArgumentException::class);
        ModuleManifest::fromArray($data, '/modules/Example');
    }

    public function testItRejectsPermissionsOutsideTheModuleNamespace(): void
    {
        $data = $this->valid();
        $data['permissions'] = ['users.manage'];
        $this->expectException(InvalidArgumentException::class);
        ModuleManifest::fromArray($data, '/modules/Example');
    }

    public function testItRejectsDuplicatePermissionsAndEvents(): void
    {
        $data = $this->valid();
        $data['permissions'] = ['example.view', 'example.view'];
        try {
            ModuleManifest::fromArray($data, '/modules/Example');
            self::fail('Expected duplicate permission to be rejected.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $data = $this->valid();
        $data['events'] = ['example.created', 'example.created'];
        $this->expectException(InvalidArgumentException::class);
        ModuleManifest::fromArray($data, '/modules/Example');
    }

    public function testProviderMustBelongToItsModuleDirectoryNamespace(): void
    {
        $data = $this->valid();
        $data['provider'] = 'Modules\\Other\\src\\OtherModule';
        $this->expectException(InvalidArgumentException::class);
        ModuleManifest::fromArray($data, '/modules/Example');
    }

    /** @return array<string, mixed> */
    private function valid(): array
    {
        return [
            'name' => 'Example',
            'slug' => 'example',
            'version' => '1.2.3',
            'provider' => 'Modules\\Example\\src\\ExampleModule',
            'cms_min_version' => '0.1.0',
            'php_min_version' => '8.3.0',
            'dependencies' => ['welcome' => '1.0.0'],
            'permissions' => ['example.view'],
            'events' => ['example.created'],
            'api_version' => '1.0',
        ];
    }
}
