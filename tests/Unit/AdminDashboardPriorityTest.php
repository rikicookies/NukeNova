<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminDashboardPriorityTest extends TestCase
{
    public function testDashboardBuildsPermissionAwareAttentionAndQuickActionData(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Admin/AdminDashboardService.php');

        self::assertStringContainsString("status <> 'published'", $source);
        self::assertStringContainsString("status = 'pending'", $source);
        self::assertStringContainsString("'Module issues'", $source);
        self::assertStringContainsString("'/admin/news/new'", $source);
        self::assertStringContainsString("'/admin/pages/new'", $source);
        self::assertStringContainsString("'/admin/downloads/new'", $source);
        self::assertStringContainsString("'/admin/web-links/new'", $source);
        self::assertStringContainsString("permissions['modules.manage']", $source);
        foreach (['comment_reports', 'download_reports', 'web_link_reports', 'private_message_reports'] as $table) {
            self::assertStringContainsString($table, $source);
        }
        self::assertGreaterThanOrEqual(4, substr_count($source, "status = 'open'"));
    }

    public function testDashboardRendersEscapedAttentionAndQuickActionLinks(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/dashboard.twig');

        self::assertStringContainsString('Needs attention', $template);
        self::assertStringContainsString('Quick actions', $template);
        self::assertStringContainsString('{{ item.label }}', $template);
        self::assertStringContainsString('{{ action.label }}', $template);
        self::assertStringNotContainsString('|raw', $template);
    }
}
