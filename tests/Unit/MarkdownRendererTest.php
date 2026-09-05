<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Blocks\MarkdownRenderer;
use NovaNuke\Core\Security\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    public function testItRendersCommonMarkdown(): void
    {
        $html = (new MarkdownRenderer(new HtmlSanitizer()))->render("## Welcome\n\nThis is **NovaNuke**.");

        self::assertStringContainsString('<h2>Welcome</h2>', $html);
        self::assertStringContainsString('<strong>NovaNuke</strong>', $html);
    }

    public function testItRejectsEmbeddedHtmlAndUnsafeLinks(): void
    {
        $html = (new MarkdownRenderer(new HtmlSanitizer()))->render(
            '<script>alert(1)</script> [unsafe](javascript:alert(1)) [safe](https://example.com)',
        );

        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringContainsString('href="https://example.com"', $html);
    }
}
