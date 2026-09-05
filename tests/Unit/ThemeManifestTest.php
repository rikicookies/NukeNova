<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Themes\ThemeManifest;
use PHPUnit\Framework\TestCase;

final class ThemeManifestTest extends TestCase
{
    public function testItBuildsAThemeManifest(): void
    {
        $manifest = ThemeManifest::fromArray($this->valid(), '/themes/sample');

        self::assertSame('sample', $manifest->slug);
        self::assertContains('left-sidebar', $manifest->positions);
        self::assertSame('color', $manifest->settings['accent']['type']);
    }

    public function testItRejectsUnsupportedSettingTypes(): void
    {
        $data = $this->valid();
        $data['settings']['accent']['type'] = 'php';
        $this->expectException(InvalidArgumentException::class);

        ThemeManifest::fromArray($data, '/themes/sample');
    }

    public function testItRejectsScreenshotTraversal(): void
    {
        $data = $this->valid();
        $data['screenshot'] = '../outside.svg';
        $this->expectException(InvalidArgumentException::class);

        ThemeManifest::fromArray($data, '/themes/sample');
    }

    /** @return array<string, mixed> */
    private function valid(): array
    {
        return [
            'name' => 'Sample',
            'slug' => 'sample',
            'version' => '1.0.0',
            'cms_min_version' => '0.1.0',
            'screenshot' => 'screenshot.svg',
            'layouts' => ['default'],
            'positions' => ['left-sidebar'],
            'settings' => ['accent' => ['type' => 'color', 'default' => '#112233']],
        ];
    }
}
