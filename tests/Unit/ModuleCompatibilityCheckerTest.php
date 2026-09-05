<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Modules\ModuleCompatibilityChecker;
use NovaNuke\Core\Modules\ModuleManifest;
use PHPUnit\Framework\TestCase;

final class ModuleCompatibilityCheckerTest extends TestCase
{
    public function testItAcceptsCompatibleDependencies(): void
    {
        $result = (new ModuleCompatibilityChecker('1.0.0', '8.3.0'))->check(
            $this->manifest(),
            ['base' => ['installed_version' => '2.1.0']],
        );

        self::assertTrue($result->compatible);
        self::assertNull($result->reason);
    }

    public function testItRejectsMissingDependencies(): void
    {
        $result = (new ModuleCompatibilityChecker('1.0.0', '8.3.0'))->check($this->manifest(), []);

        self::assertFalse($result->compatible);
        self::assertStringContainsString('Missing dependency', (string) $result->reason);
    }

    private function manifest(): ModuleManifest
    {
        return ModuleManifest::fromArray([
            'name' => 'Feature',
            'slug' => 'feature',
            'version' => '1.0.0',
            'provider' => 'Modules\\Feature\\src\\FeatureModule',
            'cms_min_version' => '0.1.0',
            'php_min_version' => '8.3.0',
            'dependencies' => ['base' => '2.0.0'],
            'permissions' => [],
        ], '/modules/Feature');
    }
}
