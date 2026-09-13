<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminNavigationOrderContractTest extends TestCase
{
    public function testNavigationOrderIsPersistedThroughSettingsAndAppliedByNavigationManager(): void
    {
        $root=dirname(__DIR__,2);
        $manager=(string)file_get_contents($root.'/app/Core/Admin/AdminNavigationManager.php');
        $controller=(string)file_get_contents($root.'/app/Admin/MenusController.php');
        $routes=(string)file_get_contents($root.'/routes/admin.php');

        self::assertStringContainsString("admin.navigation.order", $manager);
        self::assertStringContainsString("applySavedOrder", $manager);
        self::assertStringContainsString("admin.navigation.order", $controller);
        self::assertStringContainsString("/admin/navigation/order", $routes);
    }
}
