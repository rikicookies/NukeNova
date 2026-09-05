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
}
