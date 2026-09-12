<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\ErrorHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProductionErrorDisclosureTest extends TestCase
{
    public function testProductionResponseHidesExceptionDetailsButKeepsReferenceId(): void
    {
        $log = sys_get_temp_dir() . '/novanuke-error-' . bin2hex(random_bytes(5)) . '.log';
        $response = (new ErrorHandler(false, $log, projectRoot: dirname(__DIR__, 2)))
            ->render(new RuntimeException('password=supersecret'));

        self::assertSame(500, $response->status());
        self::assertStringContainsString('Reference:', $response->content());
        self::assertStringNotContainsString('supersecret', $response->content());

        $contents = (string) file_get_contents($log);
        self::assertStringNotContainsString('supersecret', $contents);
        self::assertStringContainsString('[APP]/tests/Unit/ProductionErrorDisclosureTest.php', $contents);
        self::assertStringNotContainsString(dirname(__DIR__, 2), $contents);

        @unlink($log);
    }
}
