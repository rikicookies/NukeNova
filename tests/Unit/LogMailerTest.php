<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Mail\LogMailer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LogMailerTest extends TestCase
{
    public function testItWritesDevelopmentMail(): void
    {
        $path = sys_get_temp_dir() . '/novanuke-mail-' . bin2hex(random_bytes(5));
        try {
            (new LogMailer($path, 'development', 'from@example.test', 'NovaNuke'))
                ->sendPasswordReset('user@example.test', 'http://localhost/reset/token', 60);
            $content = file_get_contents($path);
            self::assertStringContainsString('user@example.test', $content);
            self::assertStringContainsString('http://localhost/reset/token', $content);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testItRefusesToLogMailInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        (new LogMailer('/unused', 'production', 'from@example.test', 'NovaNuke'))
            ->sendPasswordReset('user@example.test', 'http://localhost/reset/token', 60);
    }
}
