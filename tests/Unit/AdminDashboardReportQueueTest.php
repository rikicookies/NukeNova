<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminDashboardReportQueueTest extends TestCase
{
    public function testEveryReportQueueRequiresItsModulePermissionAndTable(): void
    {
        $service = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Admin/AdminDashboardService.php');
        $controller = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Admin/AdminDashboardController.php');

        foreach ([
            'comments.moderate' => 'comment_reports',
            'downloads.manage' => 'download_reports',
            'web-links.manage' => 'web_link_reports',
            'private-messages.moderate' => 'private_message_reports',
        ] as $permission => $table) {
            self::assertStringContainsString("permissions['{$permission}']", $service);
            self::assertStringContainsString("tableExists('{$table}')", $service);
            self::assertStringContainsString("'{$permission}'", $controller);
        }
    }

    public function testReportLinksUseExistingProtectedAdminDestinations(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Admin/AdminDashboardService.php');

        foreach (['/admin/comments', '/admin/downloads', '/admin/web-links', '/admin/private-messages'] as $url) {
            self::assertStringContainsString("'{$url}'", $source);
        }
    }
}
