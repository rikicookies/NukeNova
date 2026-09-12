<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PublicBreadcrumbsTest extends TestCase
{
    public function testSharedBreadcrumbComponentIsAccessibleAndEscapedByTwig(): void
    {
        $source = $this->source('resources/views/components/breadcrumbs.twig');

        self::assertStringContainsString('aria-label="Breadcrumb"', $source);
        self::assertStringContainsString('aria-current="page"', $source);
        self::assertStringNotContainsString('|raw', $source);
    }

    public function testPrimaryContentListsAndDetailsUseTheSharedComponent(): void
    {
        foreach ([
            'modules/News/views/index.twig',
            'modules/News/views/show.twig',
            'modules/Pages/views/index.twig',
            'modules/Pages/views/default.twig',
            'modules/Pages/views/landing.twig',
            'modules/Downloads/views/index.twig',
            'modules/Downloads/views/show.twig',
            'modules/WebLinks/views/index.twig',
            'modules/WebLinks/views/show.twig',
        ] as $file) {
            self::assertStringContainsString("components/breadcrumbs.twig", $this->source($file), $file);
        }
    }

    public function testPageBreadcrumbsPreserveTheVisibleParentHierarchy(): void
    {
        foreach (['modules/Pages/views/default.twig', 'modules/Pages/views/landing.twig'] as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('page.parent_slug', $source);
            self::assertStringContainsString('page.parent_title', $source);
            self::assertStringContainsString("'/pages/' ~ page.parent_slug", $source);
        }
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
