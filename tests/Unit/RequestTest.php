<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testRefererRejectsControlCharacters(): void
    {
        $safe = new Request('GET', '/', [], [], [], [], ['HTTP_REFERER' => 'https://example.com/page']);
        $unsafe = new Request('GET', '/', [], [], [], [], ['HTTP_REFERER' => "https://example.com\nInjected"]);
        self::assertSame('https://example.com/page', $safe->referer());
        self::assertSame('', $unsafe->referer());
    }
    #[DataProvider('paths')]
    public function testItNormalizesPaths(string $uri, string $expected): void
    {
        self::assertSame($expected, Request::create('GET', $uri)->path());
    }

    public static function paths(): array
    {
        return [
            'root' => ['/', '/'],
            'trailing slash' => ['/news/', '/news'],
            'query string' => ['/news?page=2', '/news'],
        ];
    }

    public function testItReturnsOnlyRequestedUploadedFileMetadata(): void
    {
        $request = new Request('POST', '/upload', files: ['package' => ['name' => 'file.zip', 'error' => UPLOAD_ERR_OK]]);

        self::assertSame('file.zip', $request->file('package')['name']);
        self::assertNull($request->file('missing'));
    }
}
