<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminTableComponentsTest extends TestCase
{
    public function testSharedTableComponentsAreAccessibleAndAutoescaped(): void
    {
        $empty = $this->source('resources/views/components/admin-table-empty.twig');
        $actions = $this->source('resources/views/components/admin-row-actions.twig');

        self::assertStringContainsString('role="status"', $empty);
        self::assertStringContainsString('colspan="{{ colspan }}"', $empty);
        self::assertStringContainsString('aria-label=', $actions);
        self::assertStringNotContainsString('|raw', $empty . $actions);
    }

    public function testPrimaryContentTablesUseSharedEmptyRowsAndActions(): void
    {
        foreach ($this->screens() as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('components/admin-table-empty.twig', $source, $file);
            self::assertStringContainsString('components/admin-row-actions.twig', $source, $file);
            self::assertStringContainsString('admin-actions-cell', $source, $file);
            self::assertStringContainsString('<th>Actions</th>', $source, $file);
        }
    }

    public function testViewActionsAreLimitedToPublishedContent(): void
    {
        foreach ($this->screens() as $file) {
            self::assertStringContainsString("status == 'published' ?", $this->source($file), $file);
        }
    }

    public function testWebLinkDeleteRemainsPostCsrfProtectedAndConfirmed(): void
    {
        $source = $this->source('modules/WebLinks/views/admin/index.twig');

        self::assertStringContainsString('method="post" action="/admin/web-links/{{ item.id }}/delete"', $source);
        self::assertStringContainsString('name="_token" value="{{ csrf_token }}"', $source);
        self::assertStringContainsString('name="confirm_delete" value="1" required', $source);
    }

    /** @return list<string> */
    private function screens(): array
    {
        return [
            'modules/News/views/admin/index.twig',
            'modules/Pages/views/admin/index.twig',
            'modules/Downloads/views/admin/index.twig',
            'modules/WebLinks/views/admin/index.twig',
        ];
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
