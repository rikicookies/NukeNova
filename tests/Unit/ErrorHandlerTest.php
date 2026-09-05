<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\ErrorHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorHandlerTest extends TestCase
{
    public function testProductionResponseAndLogDoNotExposeSecrets(): void
    {
        $path = sys_get_temp_dir() . '/novanuke-errors-' . bin2hex(random_bytes(5));
        try {
            $response = (new ErrorHandler(false, $path))->render(
                new RuntimeException('Connection failed password=hunter2 token:abcdef'),
            );
            self::assertSame(500, $response->status());
            self::assertStringNotContainsString('hunter2', $response->content());
            self::assertStringNotContainsString('abcdef', $response->content());
            $log = (string) file_get_contents($path);
            self::assertStringNotContainsString('hunter2', $log);
            self::assertStringNotContainsString('abcdef', $log);
            self::assertStringContainsString('[REDACTED]', $log);
        } finally {
            if (is_file($path)) unlink($path);
        }
    }

    public function testDebugResponseRedactsCredentials(): void
    {
        $path = sys_get_temp_dir() . '/novanuke-errors-' . bin2hex(random_bytes(5));
        try {
            $response = (new ErrorHandler(true, $path))->render(new RuntimeException('api_key=secret-value'));
            self::assertStringNotContainsString('secret-value', $response->content());
        } finally {
            if (is_file($path)) unlink($path);
        }
    }
}
