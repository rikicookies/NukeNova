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
        ];
    }
}
