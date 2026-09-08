<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Admin\AdminMenuBuilding;
use PHPUnit\Framework\TestCase;

final class AdminMenuBuildingTest extends TestCase
{
    public function testModulesCanRegisterAdministrativeLinks(): void
    {
        $menu = new AdminMenuBuilding();
        $menu->add('News', '/admin/news', 'news.edit');

        self::assertCount(1, $menu->items());
        self::assertSame('/admin/news', $menu->items()[0]['url']);
        self::assertSame('module', $menu->items()[0]['icon']);
        self::assertSame('modules', $menu->items()[0]['group']);
    }

    public function testModulesCanProvideAnIconAndNavigationGroup(): void
    {
        $menu = new AdminMenuBuilding();
        $menu->add('News', '/admin/news', 'news.edit', 'newspaper', 'content');

        self::assertSame('newspaper', $menu->items()[0]['icon']);
        self::assertSame('content', $menu->items()[0]['group']);
    }

    public function testItRejectsExternalAdministrativeLinks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AdminMenuBuilding())->add('Bad', 'https://example.com', 'admin.access');
    }

    public function testItRejectsUnsafeIconNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AdminMenuBuilding())->add('Bad', '/admin/bad', 'admin.access', '../icon', 'system');
    }
}
