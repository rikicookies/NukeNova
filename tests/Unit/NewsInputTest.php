<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\News\src\NewsInput;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NewsInputTest extends TestCase
{
    public function testEditorCanPrepareAFormattedDraftAndPreserveSource(): void
    {
        $data = $this->input()->article([
            'title' => 'First news', 'slug' => 'first-news', 'status' => 'draft',
            'content' => '<p>Hello</p><script>alert(1)</script>', 'content_format' => 'html',
            'summary' => '**Intro**', 'summary_format' => 'markdown', 'tags' => 'Nova, CMS',
        ], false);

        self::assertSame('draft', $data['status']);
        self::assertStringContainsString('<script', $data['content']);
        self::assertSame('html', $data['content_format']);
        self::assertSame('markdown', $data['summary_format']);
        self::assertSame(['nova' => 'Nova', 'cms' => 'CMS'], $data['tags']);
    }

    public function testEditorCannotPublishWithoutPermission(): void
    {
        $this->expectException(RuntimeException::class);
        $this->input()->article(['title' => 'News', 'slug' => 'news', 'status' => 'published', 'content' => '<p>Text</p>'], false);
    }

    public function testItAcceptsVipAudienceAndRejectsUnknownAudience(): void
    {
        $data = $this->input()->article(['title' => 'VIP', 'slug' => 'vip', 'status' => 'draft', 'content' => 'Private', 'audience' => 'vip'], false);
        self::assertSame('vip', $data['audience']);

        $this->expectException(RuntimeException::class);
        $this->input()->article(['title' => 'Bad', 'slug' => 'bad', 'status' => 'draft', 'content' => 'Private', 'audience' => 'admin'], false);
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

    public function testUnknownContentFormatIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->input()->article(['title' => 'Bad', 'slug' => 'bad', 'status' => 'draft', 'content' => 'Text', 'content_format' => 'php'], false);
    }

    private function input(): NewsInput
    {
        return new NewsInput();
    }
}
