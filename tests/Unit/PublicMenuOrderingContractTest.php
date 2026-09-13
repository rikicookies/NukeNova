<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PublicMenuOrderingContractTest extends TestCase
{
    public function testPublicMenuOrderHasProtectedPersistenceEndpoint(): void
    {
        $root=dirname(__DIR__,2);
        $repository=(string)file_get_contents($root.'/app/Core/Menus/MenuRepository.php');
        $controller=(string)file_get_contents($root.'/app/Admin/MenusController.php');
        $routes=(string)file_get_contents($root.'/routes/admin.php');

        self::assertStringContainsString('function reorderItems', $repository);
        self::assertStringContainsString('The submitted menu order does not match the menu items.', $repository);
        self::assertStringContainsString('existing parent level', $repository);
        self::assertStringContainsString('savePublicMenuOrder', $controller);
        self::assertStringContainsString("'/admin/menus/{id}/order'", $routes);
    }

    public function testPublicMenuSorterHasDesktopAndTouchControls(): void
    {
        $root=dirname(__DIR__,2);
        $template=(string)file_get_contents($root.'/resources/views/admin/menus/index.twig');
        $script=(string)file_get_contents($root.'/public/assets/js/public-menu-order.js');

        self::assertStringContainsString('data-public-menu-order', $template);
        self::assertStringContainsString('admin-nav-move-buttons', $template);
        self::assertStringContainsString('data-public-move="up"', $template);
        self::assertStringContainsString('data-public-move="down"', $template);
        self::assertStringContainsString('draggable="true"', $template);
        self::assertStringContainsString('JSON.stringify', $script);
    }

    public function testPrimaryNavigationHasDedicatedVisualSorter(): void
    {
        $template=(string)file_get_contents(dirname(__DIR__,2).'/resources/views/admin/menus/index.twig');
        self::assertStringContainsString("primary_menu.slug == 'primary'", $template);
        self::assertStringContainsString('Primary navigation order', $template);
        self::assertStringContainsString('Save primary navigation order', $template);
        self::assertStringContainsString('data-public-menu-order', $template);
    }

    public function testPrimarySorterUsesRenderedTree(): void
    {
        $root=dirname(__DIR__,2);
        $manager=(string)file_get_contents($root.'/app/Core/Menus/MenuManager.php');
        $template=(string)file_get_contents($root.'/resources/views/admin/menus/index.twig');
        $script=(string)file_get_contents($root.'/public/assets/js/public-menu-order.js');

        self::assertStringContainsString("\$menu['tree'] = \$this->trees->build", $manager);
        self::assertStringContainsString('sortable_tree(primary_menu.tree', $template);
        self::assertStringContainsString('serializeList', $script);
        self::assertStringContainsString('candidate.parentElement !== dragged.parentElement', $script);
    }
}
