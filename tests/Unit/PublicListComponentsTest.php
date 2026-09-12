<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PublicListComponentsTest extends TestCase
{
    public function testPaginationComponentMarksTheCurrentPageAndEscapesItsUrl(): void
    {
        $source = $this->source('resources/views/components/pagination.twig');

        self::assertStringContainsString('aria-label=', $source);
        self::assertStringContainsString('aria-current="page"', $source);
        self::assertStringContainsString("url_template|replace({'{page}': page_number})", $source);
        self::assertStringNotContainsString('|raw', $source);
    }

    public function testEmptyStateIsReusableAccessibleAndAutoescaped(): void
    {
        $source = $this->source('resources/views/components/empty-state.twig');

        self::assertStringContainsString('role="status"', $source);
        self::assertStringContainsString('action_url', $source);
        self::assertStringNotContainsString('|raw', $source);
    }

    public function testPrimaryDirectoriesUseSharedEmptyStates(): void
    {
        foreach ($this->directories() as $file) {
            self::assertStringContainsString('components/empty-state.twig', $this->source($file), $file);
        }
    }

    public function testPaginatedDirectoriesUseTheSharedPaginationComponent(): void
    {
        foreach (array_diff($this->directories(), ['modules/Pages/views/index.twig']) as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('components/pagination.twig', $source, $file);
            self::assertStringNotContainsString('<nav class="pagination">', $source, $file);
        }

        foreach (['modules/Downloads/views/index.twig', 'modules/WebLinks/views/index.twig'] as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('search|url_encode', $source, $file);
            self::assertStringContainsString('order|url_encode', $source, $file);
        }
    }

    /** @return list<string> */
    private function directories(): array
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
