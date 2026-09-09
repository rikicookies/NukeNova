<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WikiEditorEnhancementTest extends TestCase
{
    public function testAttachmentSnippetsUseTheExistingMarkdownEditor(): void
    {
        $root = dirname(__DIR__, 2);
        $view = (string) file_get_contents($root . '/modules/Wiki/views/admin/edit.twig');
        $script = (string) file_get_contents($root . '/public/assets/js/wiki-editor.js');

        self::assertStringContainsString('data-wiki-editor', $view);
        self::assertStringContainsString('data-wiki-insert="{{ attachment.markdown_link }}"', $view);
        self::assertStringContainsString('data-wiki-insert="{{ attachment.markdown_image }}"', $view);
        self::assertStringContainsString('/assets/js/wiki-editor.js', $view);
        self::assertStringContainsString('data-wiki-link-label', $view);
        self::assertStringContainsString('data-wiki-link-path', $view);
        self::assertStringContainsString('data-wiki-link-insert', $view);
        self::assertStringContainsString('formaction="/admin/wiki/preview"', $view);
        self::assertStringContainsString('formmethod="post"', $view);
        self::assertStringContainsString('formtarget="_blank"', $view);
        self::assertStringContainsString('setRangeText', $script);
        self::assertStringContainsString('`[${label}](/wiki/${path})`', $script);
        self::assertStringContainsString('setCustomValidity', $script);
        self::assertStringContainsString("addEventListener('input'", $script);
        self::assertStringContainsString("dispatchEvent(new Event('input'", $script);

        $preview = (string) file_get_contents($root . '/modules/Wiki/views/admin/preview.twig');
        self::assertStringContainsString('Preview only — nothing has been saved.', $preview);
        self::assertStringContainsString('{{ page.content_html }}', $preview);
    }
}
