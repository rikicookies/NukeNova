<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Installer\EnvWriter;
use PHPUnit\Framework\TestCase;

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
}
