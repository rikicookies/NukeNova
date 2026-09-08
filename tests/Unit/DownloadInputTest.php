<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Downloads\src\DownloadInput;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DownloadInputTest extends TestCase
{
    public function testItNormalizesAValidExternalDownload(): void
    {
        $data = (new DownloadInput())->download([
            'name' => 'Example package', 'slug' => 'example-package', 'description' => '<p>Safe</p><script>bad()</script>',
            'description_format' => 'html', 'requirements' => '**PHP 8.3**', 'requirements_format' => 'markdown',
            'source_type' => 'external', 'external_url' => 'https://example.test/file.zip', 'status' => 'draft',
            'access_type' => 'roles', 'role_ids' => ['2', '2'], 'is_featured' => '1',
        ], false);

        self::assertSame('https://example.test/file.zip', $data['external_url']);
        self::assertSame([2], $data['role_ids']);
        self::assertSame(1, $data['is_featured']);
        self::assertStringContainsString('<script', $data['description']);
        self::assertSame('html', $data['description_format']);
        self::assertSame('markdown', $data['requirements_format']);
    }

    public function testItRejectsDangerousExternalProtocols(): void
    {
        $this->expectException(RuntimeException::class);
        (new DownloadInput())->download(array_replace($this->valid(), ['source_type' => 'external', 'external_url' => 'javascript:alert(1)']), false);
    }

    public function testManagersCannotPublishWithoutPermission(): void
    {
        $this->expectException(RuntimeException::class);
        (new DownloadInput())->download(array_replace($this->valid(), ['status' => 'published']), false);
    }

    public function testItRejectsUnknownDescriptionFormats(): void
    {
        $this->expectException(RuntimeException::class);
        (new DownloadInput())->download(array_replace($this->valid(), ['description_format' => 'php']), false);
    }

    private function valid(): array
    {
        return ['name' => 'Example', 'slug' => 'example', 'description' => '<p>Safe</p>', 'source_type' => 'local', 'status' => 'draft', 'access_type' => 'public'];
    }
}
