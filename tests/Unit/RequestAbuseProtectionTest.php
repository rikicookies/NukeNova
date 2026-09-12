<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RequestAbuseProtectionTest extends TestCase
{
    /** @var array<string,mixed> */
    private array $get;
    /** @var array<string,mixed> */
    private array $post;
    /** @var array<string,mixed> */
    private array $server;

    protected function setUp(): void
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        $_GET = $this->get;
        $_POST = $this->post;
        $_SERVER = $this->server;
    }

    public function testCaptureRejectsExcessiveQueryParameters(): void
    {
        $_GET = array_fill_keys(array_map(static fn (int $i): string => 'q' . $i, range(1, 201)), 'x');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Too many query parameters');
        Request::capture();
    }

    public function testCaptureRejectsExcessiveRequestParameters(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array_fill_keys(array_map(static fn (int $i): string => 'p' . $i, range(1, 501)), 'x');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Too many request parameters');
        Request::capture();
    }

    public function testCaptureRejectsDeeplyNestedInput(): void
    {
        $_POST = ['x' => ['x' => ['x' => ['x' => ['x' => ['x' => ['x' => ['x' => ['x' => 'boom']]]]]]]]];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nested too deeply');
        Request::capture();
    }
}
