<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Version;
use PHPUnit\Framework\TestCase;

final class ReleaseVersionTest extends TestCase
{
    public function testReleaseUsesTheExpectedDevelopmentVersion(): void
    {
        self::assertSame('0.2.0-alpha.1', Version::CURRENT);
    }

    public function testBundledModulesAndThemesAcceptTheCurrentRelease(): void
    {
        $root = dirname(__DIR__, 2);
        $files = array_merge(
            glob($root . '/modules/*/module.json') ?: [],
            glob($root . '/themes/*/theme.json') ?: [],
        );
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $manifest = json_decode((string) file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
            self::assertTrue(
                version_compare(Version::CURRENT, (string) $manifest['cms_min_version'], '>='),
                basename(dirname($file)) . ' requires ' . $manifest['cms_min_version'],
            );
        }
    }
}
