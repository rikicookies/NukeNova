<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExternalContentLinkTemplateTest extends TestCase
{
    public function testRecommendedLinksOpenSafelyInANewTab(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/WebLinks/views/show.twig');
        self::assertStringContainsString('target="_blank"', $template);
        self::assertStringContainsString('rel="noopener noreferrer nofollow external"', $template);
    }

    public function testOnlyExternalDownloadsRequestANewTab(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Downloads/views/show.twig');
        self::assertStringContainsString("{% if download.source_type == 'external' %}", $template);
        self::assertStringContainsString('target="_blank" rel="noopener noreferrer external"', $template);
    }
}
