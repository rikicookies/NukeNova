<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Downloads\src\DownloadInput;
use NovaNuke\Core\Security\HtmlSanitizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DownloadInputTest extends TestCase
{
    public function testItNormalizesAValidExternalDownload(): void
    {
        $data = (new DownloadInput(new HtmlSanitizer()))->download([
            'name' => 'Example package', 'slug' => 'example-package', 'description' => '<p>Safe</p><script>bad()</script>',
            'source_type' => 'external', 'external_url' => 'https://example.test/file.zip', 'status' => 'draft',
            'access_type' => 'roles', 'role_ids' => ['2', '2'], 'is_featured' => '1',
        ], false);

        self::assertSame('https://example.test/file.zip', $data['external_url']);
        self::assertSame([2], $data['role_ids']);
        self::assertSame(1, $data['is_featured']);
        self::assertStringNotContainsString('<script', $data['description']);
    }

    public function testItRejectsDangerousExternalProtocols(): void
    {
        $this->expectException(RuntimeException::class);
        (new DownloadInput(new HtmlSanitizer()))->download(array_replace($this->valid(), ['source_type' => 'external', 'external_url' => 'javascript:alert(1)']), false);
    }

    public function testManagersCannotPublishWithoutPermission(): void
    {
        $this->expectException(RuntimeException::class);
        (new DownloadInput(new HtmlSanitizer()))->download(array_replace($this->valid(), ['status' => 'published']), false);
    }

    private function valid(): array
    {
        return ['name' => 'Example', 'slug' => 'example', 'description' => '<p>Safe</p>', 'source_type' => 'local', 'status' => 'draft', 'access_type' => 'public'];
    }
}
