<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminDashboardHealthTest extends TestCase
{
    public function testHealthInspectionAndRenderingRemainPermissionAware(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Admin/AdminDashboardController.php');
        $template = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/dashboard.twig');

        self::assertStringContainsString("permissions['settings.manage'] ? \$this->system->inspect() : null", $controller);
        self::assertStringContainsString("'system_health' => \$system === null ? null", $controller);
        self::assertStringContainsString('Site health', $template);
        self::assertStringContainsString('{{ check.value }}', $template);
        self::assertStringNotContainsString('|raw', $template);
    }

    public function testSystemInspectorPublishesMaintenanceStateWithoutSecrets(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/System/SystemInspector.php');

        self::assertStringContainsString("'maintenance' => \$this->settings->boolean('system.maintenance', false)", $source);
        self::assertStringNotContainsString("'mail_password'", $source);
    }
}
