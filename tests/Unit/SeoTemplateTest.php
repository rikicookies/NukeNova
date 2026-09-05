<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SeoTemplateTest extends TestCase
{
    public function testPublicContentTemplatesExposeCanonicalAndSocialMetadata(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['modules/News/views/show.twig','modules/Pages/views/default.twig','modules/Pages/views/landing.twig'] as $relative) {
            $template = (string) file_get_contents($root . '/' . $relative);
            self::assertStringContainsString('rel="canonical"', $template, $relative);
            self::assertStringContainsString('property="og:title"', $template, $relative);
            self::assertStringContainsString('name="twitter:card"', $template, $relative);
        }
    }
}
