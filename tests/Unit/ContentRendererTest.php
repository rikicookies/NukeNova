<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Blocks\MarkdownRenderer;
use NovaNuke\Core\Content\ContentFormat;
use NovaNuke\Core\Content\ContentProfile;
use NovaNuke\Core\Content\ContentRenderer;
use NovaNuke\Core\Security\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class ContentRendererTest extends TestCase
{
    private ContentRenderer $renderer;

    protected function setUp(): void
    {
        $html = new HtmlSanitizer();
        $this->renderer = new ContentRenderer($html, new MarkdownRenderer($html));
    }

    public function testItSanitizesHtmlAtRenderTime(): void
    {
        $html = $this->renderer->render('<h2>Hello</h2><script>alert(1)</script>', ContentFormat::Html);
        self::assertStringContainsString('<h2>Hello</h2>', $html);
        self::assertStringNotContainsString('script', $html);
    }

    public function testItRendersMarkdownWithoutExecutingEmbeddedHtml(): void
    {
        $html = $this->renderer->render("## Hello\n\n**Safe**<script>alert(1)</script>", ContentFormat::Markdown, ContentProfile::FullContent);
        self::assertStringContainsString('<h2>Hello</h2>', $html);
        self::assertStringContainsString('<strong>Safe</strong>', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testCommentProfileRemovesFullContentOnlyElements(): void
    {
        $html = $this->renderer->render('<h2>Heading</h2><p><strong>Allowed</strong></p><div>Wrapper<script>alert(1)</script></div>', ContentFormat::Html, ContentProfile::Comment);
        self::assertStringNotContainsString('<h2', $html);
        self::assertStringNotContainsString('<div', $html);
        self::assertStringContainsString('<strong>Allowed</strong>', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testMessageProfileKeepsFormattingButRemovesUnsafeMarkup(): void
    {
        $html = $this->renderer->render('<h2>Heading</h2><p><strong>Safe</strong> <a href="javascript:alert(1)">link</a></p><script>alert(1)</script>', ContentFormat::Html, ContentProfile::Message);
        self::assertStringNotContainsString('<h2', $html);
        self::assertStringContainsString('<strong>Safe</strong>', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testFullMarkdownAllowsSafeImagesButDescriptionDoesNot(): void
    {
        $source = '![Diagram](/wiki/attachments/12?inline=1)';

        self::assertStringContainsString('<img', $this->renderer->render($source, ContentFormat::Markdown, ContentProfile::FullContent));
        self::assertStringNotContainsString('<img', $this->renderer->render($source, ContentFormat::Markdown, ContentProfile::Description));
    }
}
