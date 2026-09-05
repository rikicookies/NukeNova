<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Pages\src\PageInput;
use NovaNuke\Core\Security\HtmlSanitizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PageInputTest extends TestCase
{
    public function testItNormalizesAndSanitizesAValidPage(): void
    {
        $page = (new PageInput(new HtmlSanitizer()))->page([
            'title' => 'About us', 'slug' => 'about-us', 'content' => '<p>Hello</p><script>alert(1)</script>',
            'status' => 'draft', 'template' => 'landing', 'access_type' => 'roles', 'role_ids' => ['2', '2'],
            'image_path' => '/uploads/pages/about.webp', 'comments_enabled' => '1',
        ], false);

        self::assertSame('about-us', $page['slug']);
        self::assertSame('landing', $page['template']);
        self::assertSame([2], $page['role_ids']);
        self::assertSame(1, $page['comments_enabled']);
        self::assertStringNotContainsString('<script', $page['content']);
    }

    public function testRoleRestrictedPagesRequireAtLeastOneRole(): void
    {
        $this->expectException(RuntimeException::class);
        (new PageInput(new HtmlSanitizer()))->page(array_replace($this->valid(), ['access_type' => 'roles']), false);
    }

    public function testItRejectsUnsafeImagePaths(): void
    {
        $this->expectException(RuntimeException::class);
        (new PageInput(new HtmlSanitizer()))->page(array_replace($this->valid(), ['image_path' => '/uploads/../secret.php']), false);
    }

    public function testEditorsCannotPublishWithoutPermission(): void
    {
        $this->expectException(RuntimeException::class);
        (new PageInput(new HtmlSanitizer()))->page(array_replace($this->valid(), ['status' => 'published']), false);
    }

    private function valid(): array
    {
        return ['title' => 'Example', 'slug' => 'example', 'content' => '<p>Safe content</p>', 'status' => 'draft', 'template' => 'default', 'access_type' => 'public'];
    }
}
