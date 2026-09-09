<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WikiSearchProviderContractTest extends TestCase
{
    public function testWikiRegistersAnOptionalGlobalSearchProvider(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $provider = (string) file_get_contents($root . '/modules/Wiki/src/WikiSearchProvider.php');
        $manifest = json_decode((string) file_get_contents($root . '/modules/Wiki/module.json'), true, 32, JSON_THROW_ON_ERROR);

        self::assertSame([], $manifest['dependencies']);
        self::assertStringContainsString("listen('search.providers.registering'", $module);
        self::assertStringContainsString('implements SearchProviderInterface', $provider);
        self::assertStringContainsString("return 'wiki'", $provider);
    }

    public function testProviderRestrictsResultsByPublicationAndViewerAudience(): void
    {
        $provider = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Wiki/src/WikiSearchProvider.php');

        self::assertStringContainsString("status='published'", $provider);
        self::assertStringContainsString('published_at<=UTC_TIMESTAMP()', $provider);
        self::assertStringContainsString('audience IN ({$audiencePlaceholders})', $provider);
        self::assertStringContainsString("allows('vip'", $provider);
        self::assertStringContainsString('LikePattern::contains($query->term)', $provider);
        self::assertStringContainsString("'/wiki/' . $path", $provider);
    }
}
