<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminNavigationConsistencyTest extends TestCase
{
    public function testPrimaryAdminListsUseSharedBreadcrumbsAndHeaders(): void
    {
        foreach ($this->screens() as $file => $routes) {
            $source = $this->source($file);
            self::assertStringContainsString('components/breadcrumbs.twig', $source, $file);
            self::assertStringContainsString("{label: 'Admin', url: '/admin'}", $source, $file);
            self::assertStringContainsString('components/page-header.twig', $source, $file);
            self::assertStringNotContainsString('Return to dashboard', $source, $file);

            foreach ($routes as $route) {
                self::assertStringContainsString("url: '{$route}'", $source, $file);
            }
        }
    }

    public function testAdminHeadersRetainTheirLargeHeadingStyle(): void
    {
        self::assertStringContainsString(
            '.admin-page > .content-page-header h1',
            $this->source('public/assets/css/app.css'),
        );
    }

    /** @return array<string,list<string>> */
    private function screens(): array
    {
        return [
            'modules/News/views/admin/index.twig' => ['/admin/news/new', '/news'],
            'modules/Pages/views/admin/index.twig' => ['/admin/pages/new', '/pages'],
            'modules/Downloads/views/admin/index.twig' => ['/admin/downloads/new', '/downloads'],
            'modules/WebLinks/views/admin/index.twig' => ['/admin/web-links/new', '/links'],
        ];
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
