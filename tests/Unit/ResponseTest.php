<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testRedirectsOnlyAcceptLocalPaths(): void
    {
        self::assertSame(302, Response::redirect('/admin')->status());

        $this->expectException(InvalidArgumentException::class);
        Response::redirect('https://malicious.example');
    }

    public function testRedirectCanUseSeeOtherAfterAFormSubmission(): void
    {
        self::assertSame(303, Response::redirect('/admin/themes', 303)->status());
    }

    public function testXmlResponsesDeclareRssAndDisableMimeSniffing(): void
    {
        $response = Response::xml('<?xml version="1.0"?><rss/>', 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=300',
        ]);

        self::assertSame('application/rss+xml; charset=UTF-8', $response->header('content-type'));
        self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
        self::assertSame('public, max-age=300', $response->header('Cache-Control'));
    }

    public function testExternalRedirectsAllowOnlySafeHttpDestinations(): void
    {
        $response = Response::externalRedirect('https://example.test/file.zip');
        self::assertSame('https://example.test/file.zip', $response->header('Location'));
        self::assertSame('no-referrer', $response->header('Referrer-Policy'));

        $this->expectException(InvalidArgumentException::class);
        Response::externalRedirect('javascript:alert(1)');
    }

    public function testDownloadResponsesSanitizeHeadersWithoutLoadingTheFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'nova-response-'); file_put_contents($path, 'data');
        try {
            $response = Response::download($path, 'bad"name.zip', "text/plain\r\nX-Test: bad");
            self::assertSame('application/octet-stream', $response->header('Content-Type'));
            self::assertStringContainsString('bad_name.zip', (string) $response->header('Content-Disposition'));
            self::assertSame('4', $response->header('Content-Length'));
        } finally { @unlink($path); }
    }

    public function testHeadersAreReplacedCaseInsensitivelyAndResponsesStayImmutable(): void
    {
        $original = Response::html('ok')->withHeader('X-Test', 'first');
        $changed = $original->withHeader('x-test', 'second');

        self::assertSame('first', $original->header('X-Test'));
        self::assertSame('second', $changed->header('X-Test'));
        self::assertCount(2, $changed->headers());
    }

    public function testResponseHeadersRejectLineBreaks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Response::html('ok')->withHeader('X-Test', "safe\r\nInjected: bad");
    }
}
