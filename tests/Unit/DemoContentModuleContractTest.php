<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DemoContentModuleContractTest extends TestCase
{
    public function testModuleIsOptionalAndHasProtectedAdministrativeInstallation(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/modules/DemoContent/module.json'), true, 32, JSON_THROW_ON_ERROR);
        $module = (string) file_get_contents($root . '/modules/DemoContent/src/DemoContentModule.php');
        $controller = (string) file_get_contents($root . '/modules/DemoContent/src/DemoContentController.php');

        self::assertSame('demo-content', $manifest['slug']);
        self::assertSame([], $manifest['dependencies']);
        self::assertStringContainsString("post('/admin/system/demo-content/install'", $module);
        self::assertStringContainsString('csrf->validate', $controller);
        self::assertStringContainsString("allows((int) \$user['id'], 'settings.manage')", $controller);
        self::assertStringContainsString('isSuperAdministrator', $controller);
        self::assertStringContainsString("input('confirm_install') !== '1'", $controller);
        self::assertStringContainsString('demo-content.installed', $controller);
    }

    public function testInstallerUsesStableOwnershipAndExistingModuleApis(): void
    {
        $root = dirname(__DIR__, 2);
        $installer = (string) file_get_contents($root . '/modules/DemoContent/src/DemoContentInstaller.php');
        $migration = (string) file_get_contents($root . '/modules/DemoContent/database/migrations/2026_09_09_000001_create_demo_content_tables.php');

        self::assertStringContainsString("DATASET = 'novatech-community-v1'", $installer);
        self::assertStringContainsString('demo_content_items', $migration);
        self::assertStringContainsString('PRIMARY KEY', $migration);
        self::assertStringContainsString('NewsRepository::class', $installer);
        self::assertStringContainsString('PageRepository::class', $installer);
        self::assertStringContainsString('DownloadRepository::class', $installer);
        self::assertStringContainsString('WebLinkRepository::class', $installer);
        self::assertStringContainsString('FriendService::class', $installer);
        self::assertStringContainsString('PrivateMessageService::class', $installer);
        self::assertStringContainsString('cleanupFailedInstallation', $installer);
        self::assertStringNotContainsString('Modules\\Wiki', $installer);
        self::assertStringNotContainsString('Core\\Blocks', $installer);
    }
}
