<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\News\src\NewsInput;
use NovaNuke\Core\Security\HtmlSanitizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NewsInputTest extends TestCase
{
    public function testEditorCanPrepareASanitizedDraft(): void
    {
        $data = $this->input()->article([
            'title' => 'First news', 'slug' => 'first-news', 'status' => 'draft',
            'content' => '<p>Hello</p><script>alert(1)</script>', 'tags' => 'Nova, CMS',
        ], false);

        self::assertSame('draft', $data['status']);
        self::assertStringNotContainsString('script', $data['content']);
        self::assertSame(['nova' => 'Nova', 'cms' => 'CMS'], $data['tags']);
    }

    public function testEditorCannotPublishWithoutPermission(): void
    {
        $this->expectException(RuntimeException::class);
        $this->input()->article(['title' => 'News', 'slug' => 'news', 'status' => 'published', 'content' => '<p>Text</p>'], false);
    }

    public function testPublisherCanScheduleForTheFuture(): void
    {
        $future = (new \DateTimeImmutable('+2 hours', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i');
        $data = $this->input()->article(['title' => 'Later', 'slug' => 'later', 'status' => 'scheduled', 'published_at' => $future, 'content' => '<p>Text</p>'], true);

        self::assertSame('scheduled', $data['status']);
        self::assertNotNull($data['published_at']);
    }

    public function testFeaturedImageMustRemainBelowUploads(): void
    {
        $this->expectException(RuntimeException::class);
        $this->input()->article(['title' => 'Image', 'slug' => 'image', 'status' => 'draft', 'content' => '<p>Text</p>', 'featured_image' => '/../secret'], true);
    }

    private function input(): NewsInput
    {
        return new NewsInput(new HtmlSanitizer());
    }
}
