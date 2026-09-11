<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Installer\EnvWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnvWriterTest extends TestCase
{
    public function testItQuotesEnvironmentValues(): void
    {
        $path = sys_get_temp_dir() . '/novanuke-env-' . bin2hex(random_bytes(5));

        try {
            (new EnvWriter())->write($path, [
                'APP_NAME' => 'My "Nova" Site',
                'APP_DEBUG' => false,
                'DB_PORT' => 3306,
            ]);

            $content = file_get_contents($path);
            self::assertStringContainsString('APP_NAME="My \\"Nova\\" Site"', $content);
            self::assertStringContainsString('APP_DEBUG=false', $content);
            self::assertStringContainsString('DB_PORT=3306', $content);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testItNeverOverwritesAnExistingEnvironmentFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'novanuke-env-existing-');
        file_put_contents($path, "APP_KEY=keep-me\n");

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('will not be overwritten');
            (new EnvWriter())->write($path, ['APP_KEY' => 'replacement']);
        } finally {
            self::assertSame("APP_KEY=keep-me\n", file_get_contents($path));
            if (is_file($path)) unlink($path);
        }
    }
}
