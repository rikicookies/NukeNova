<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PublicPageHeaderTest extends TestCase
{
    public function testSharedHeaderIsAccessibleAndAutoescaped(): void
    {
        $source = $this->source('resources/views/components/page-header.twig');

        self::assertStringContainsString('content-page-header', $source);
        self::assertStringContainsString('aria-label="Page actions"', $source);
        self::assertStringNotContainsString('|raw', $source);
    }

    public function testPrimaryDirectoriesUseTheSharedHeader(): void
    {
        foreach ($this->views() as $file) {
            self::assertStringContainsString('components/page-header.twig', $this->source($file), $file);
        }
    }

    public function testManageLinksAreCalculatedWithExistingPermissions(): void
    {
        foreach ([
            'modules/News/src/PublicNewsController.php' => ['news.edit', '/admin/news'],
            'modules/Pages/src/PublicPagesController.php' => ['pages.edit', '/admin/pages'],
            'modules/Downloads/src/PublicDownloadsController.php' => ['downloads.manage', '/admin/downloads'],
            'modules/WebLinks/src/PublicWebLinksController.php' => ['web-links.manage', '/admin/web-links'],
        ] as $file => [$permission, $url]) {
            $source = $this->source($file);
            self::assertStringContainsString("allows(", $source, $file);
            self::assertStringContainsString("'{$permission}'", $source, $file);
            self::assertStringContainsString("'{$url}'", $source, $file);
            self::assertStringContainsString('manage_url', $source, $file);
        }
    }

    public function testPublicHeadersKeepUsefulNonAdministrativeActions(): void
    {
        self::assertStringContainsString('/news/rss.xml', $this->source('modules/News/views/index.twig'));
        self::assertStringContainsString('/links/submit', $this->source('modules/WebLinks/views/index.twig'));
    }

    /** @return list<string> */
    private function views(): array
    {
        return [
            'modules/News/views/index.twig',
            'modules/Pages/views/index.twig',
            'modules/Downloads/views/index.twig',
            'modules/WebLinks/views/index.twig',
        ];
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
