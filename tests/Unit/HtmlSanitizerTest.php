<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Security\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class HtmlSanitizerTest extends TestCase
{
    public function testItKeepsSafeEnrichedHtml(): void
    {
        $html = (new HtmlSanitizer())->sanitize('<p><strong>Hello</strong> <a href="https://example.com" target="_blank">site</a></p>');

        self::assertStringContainsString('<strong>Hello</strong>', $html);
        self::assertStringContainsString('href="https://example.com"', $html);
        self::assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testItRemovesExecutableMarkupAndDangerousUrls(): void
    {
        $html = (new HtmlSanitizer())->sanitize('<script>alert(1)</script><p onclick="run()">Safe <a href="javascript:alert(1)">link</a></p><iframe src="/bad"></iframe>');

        self::assertStringNotContainsString('script', $html);
        self::assertStringNotContainsString('onclick', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringNotContainsString('iframe', $html);
        self::assertStringContainsString('Safe', $html);
    }

    public function testItKeepsSafeImagesAndRejectsUnsafeImageSources(): void
    {
        $html = (new HtmlSanitizer())->sanitize(
            '<img src="/wiki/attachments/12?inline=1" alt="Diagram" onerror="run()">'
            . '<img src="javascript:alert(1)"><img src="//tracker.example/pixel.png">'
            . '<img src="https://tracker.example/pixel.png">',
        );

        self::assertStringContainsString('src="/wiki/attachments/12?inline=1"', $html);
        self::assertStringContainsString('alt="Diagram"', $html);
        self::assertStringContainsString('loading="lazy"', $html);
        self::assertStringNotContainsString('onerror', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringNotContainsString('tracker.example', $html);
    }
}
