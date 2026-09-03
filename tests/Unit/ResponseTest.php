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
}
