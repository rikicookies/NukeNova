<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminExperienceCompletionTest extends TestCase
{
    public function testRemainingAdministrationScreensUseSharedNavigationAndHeaders(): void
    {
        foreach ([
            'resources/views/admin/general-settings.twig',
            'resources/views/admin/system.twig',
            'resources/views/admin/user-settings.twig',
            'resources/views/admin/logs/index.twig',
            'resources/views/admin/menus/index.twig',
            'resources/views/admin/modules/index.twig',
            'resources/views/admin/themes/index.twig',
            'modules/Comments/views/admin/index.twig',
            'modules/DemoContent/views/admin/index.twig',
            'modules/Media/views/admin/index.twig',
            'modules/Polls/views/admin/index.twig',
            'modules/PrivateMessages/views/admin/index.twig',
            'modules/Search/views/admin/index.twig',
            'modules/Statistics/views/admin/index.twig',
            'modules/Wiki/views/admin/index.twig',
            'modules/Wiki/views/admin/edit.twig',
            'modules/Wiki/views/admin/history.twig',
            'modules/Wiki/views/admin/compare.twig',
            'modules/Wiki/views/admin/revision.twig',
        ] as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('components/breadcrumbs.twig', $source, $file);
            self::assertStringContainsString("{label: 'Admin', url: '/admin'}", $source, $file);
            self::assertStringContainsString('components/page-header.twig', $source, $file);
        }
    }

    public function testLegacyReturnLinksAreRemovedFromCompletedAdministrationScreens(): void
    {
        foreach ([
            'resources/views/admin/general-settings.twig',
            'resources/views/admin/system.twig',
            'resources/views/admin/user-settings.twig',
            'resources/views/admin/logs/index.twig',
            'resources/views/admin/menus/index.twig',
            'resources/views/admin/modules/index.twig',
            'resources/views/admin/themes/index.twig',
            'modules/Comments/views/admin/index.twig',
            'modules/Wiki/views/admin/edit.twig',
            'modules/Wiki/views/admin/history.twig',
            'modules/Wiki/views/admin/compare.twig',
            'modules/Wiki/views/admin/revision.twig',
        ] as $file) {
            $source = $this->source($file);
            self::assertStringNotContainsString('Return to dashboard', $source, $file);
            self::assertStringNotContainsString('Back to dashboard', $source, $file);
            self::assertStringNotContainsString('Return to Wiki', $source, $file);
            self::assertStringNotContainsString('Return to revision history', $source, $file);
        }
    }

    public function testDashboardAndBlocksRemainExplicitlyOutsideThisPass(): void
    {
        self::assertStringNotContainsString('components/page-header.twig', $this->source('resources/views/admin/dashboard.twig'));
        self::assertStringNotContainsString('components/page-header.twig', $this->source('resources/views/admin/blocks/index.twig'));
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
