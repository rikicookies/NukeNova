<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Developer\ModuleInspector;
use PHPUnit\Framework\TestCase;

final class ModuleInspectorTest extends TestCase
{
    public function testQuotesInspectionExposesManifestRoutesPermissionsAndEvents(): void
    {
        $root = dirname(__DIR__, 2);
        $info = (new ModuleInspector())->inspect($root . '/modules/Quotes');

        self::assertSame('quotes', $info['slug']);
        self::assertSame('1.0', $info['api_version']);
        self::assertContains('quotes.manage', $info['permissions']);
        self::assertNotEmpty($info['routes']);
        self::assertContains('ADMIN_MENU_BUILDING', $info['listens']);
        self::assertTrue($info['readme']);
    }

    public function testInventoryReturnsEveryBundledManifestInSlugOrder(): void
    {
        $root = dirname(__DIR__, 2);
        $items = (new ModuleInspector())->inventory($root . '/modules');
        $slugs = array_column($items, 'slug');
        $expected = $slugs;
        sort($expected);

        self::assertSame($expected, $slugs);
        self::assertCount(count(glob($root . '/modules/*/module.json') ?: []), $items);
        self::assertContains('quotes', $slugs);
    }
}
