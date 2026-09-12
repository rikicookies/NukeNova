<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminContentEditorConsistencyTest extends TestCase
{
    public function testPrimaryContentEditorsUseSharedAdminNavigation(): void
    {
        foreach ([
            'modules/News/views/admin/edit.twig' => ["{label: 'News', url: '/admin/news'}", 'Return to news'],
            'modules/Pages/views/admin/edit.twig' => ["{label: 'Pages', url: '/admin/pages'}", 'Return to pages'],
            'modules/Downloads/views/admin/edit.twig' => ["{label: 'Downloads', url: '/admin/downloads'}", 'Return to downloads'],
            'modules/WebLinks/views/admin/edit.twig' => ["{label: 'Web Links', url: '/admin/web-links'}", 'Return to Web Links'],
        ] as $file => [$parentCrumb, $legacyLink]) {
            $source = $this->source($file);
            self::assertStringContainsString('components/breadcrumbs.twig', $source, $file);
            self::assertStringContainsString("{label: 'Admin', url: '/admin'}", $source, $file);
            self::assertStringContainsString($parentCrumb, $source, $file);
            self::assertStringContainsString('components/page-header.twig', $source, $file);
            self::assertStringNotContainsString($legacyLink, $source, $file);
        }
    }

    public function testExistingContentEditorFormsRemainPostAndCsrfProtected(): void
    {
        foreach ([
            'modules/News/views/admin/edit.twig',
            'modules/Pages/views/admin/edit.twig',
            'modules/Downloads/views/admin/edit.twig',
            'modules/WebLinks/views/admin/edit.twig',
        ] as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('method="post"', $source, $file);
            self::assertStringContainsString('name="_token"', $source, $file);
        }
    }

    public function testWebLinksEditorUsesContextAwareTitle(): void
    {
        $source = $this->source('modules/WebLinks/views/admin/edit.twig');
        self::assertStringContainsString("link.id|default(null) ? 'Edit link' : 'Create link'", $source);
        self::assertStringContainsString('{% block title %}{{ editor_title }} — NovaNuke{% endblock %}', $source);
        self::assertStringNotContainsString('Edit recommended link — NovaNuke', $source);
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
