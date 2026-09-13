<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class NovaModernMobileHardeningTest extends TestCase
{
    public function testMobileSidebarOwnsItsViewportScroll(): void
    {
        $css=(string)file_get_contents(dirname(__DIR__,2).'/themes/novamodern/assets/css/novamodern.css');
        self::assertStringContainsString('100dvh', $css);
        self::assertStringContainsString('overflow-y: auto', $css);
        self::assertStringContainsString('-webkit-overflow-scrolling: touch', $css);
        self::assertStringContainsString('safe-area-inset-bottom', $css);
        self::assertStringContainsString('body.nova-modern.nav-open { overflow: hidden;', $css);
    }

    public function testAdminTablesStayInsideMobileViewport(): void
    {
        $css=(string)file_get_contents(dirname(__DIR__,2).'/themes/novamodern/assets/css/novamodern.css');
        self::assertStringContainsString('.nova-admin .admin-table { width: max-content; min-width: 100%; }', $css);
        self::assertStringContainsString('.nova-admin .admin-table-empty-row td', $css);
        self::assertStringContainsString('position: sticky', $css);
        self::assertStringContainsString('overflow-wrap: anywhere', $css);
    }

    public function testAdminNavigationSorterHasTouchFallbackControls(): void
    {
        $template=(string)file_get_contents(dirname(__DIR__,2).'/resources/views/admin/menus/index.twig');
        $script=(string)file_get_contents(dirname(__DIR__,2).'/public/assets/js/admin-navigation-order.js');
        self::assertStringContainsString('data-admin-nav-order', $template);
        self::assertStringContainsString('data-move="up"', $template);
        self::assertStringContainsString('data-move="down"', $template);
        self::assertStringContainsString('JSON.stringify(state)', $script);
    }
}
