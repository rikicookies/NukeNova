<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CommentContentFormatTest extends TestCase
{
    public function testCommentsPersistAndRenderExplicitFormats(): void
    {
        $root = dirname(__DIR__, 2);
        $repository = (string) file_get_contents($root . '/modules/Comments/src/CommentRepository.php');
        $service = (string) file_get_contents($root . '/modules/Comments/src/CommentService.php');
        $view = (string) file_get_contents($root . '/modules/Comments/views/thread.twig');

        self::assertStringContainsString('body_format', $repository);
        self::assertStringContainsString('ContentProfile::Comment', $service);
        self::assertStringContainsString('ContentFormat::Markdown', $service);
        self::assertStringContainsString('comment.body_html', $view);
        self::assertStringContainsString('name="body_format"', $view);
    }
}
