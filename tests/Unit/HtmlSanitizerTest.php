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
}
