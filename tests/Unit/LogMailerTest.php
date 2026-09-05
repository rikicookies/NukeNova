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

    public function testItWritesEmailVerificationMail(): void
    {
        $path = sys_get_temp_dir() . '/novanuke-verification-' . bin2hex(random_bytes(5));
        try {
            (new LogMailer($path, 'development', 'from@example.test', 'NovaNuke'))
                ->sendEmailVerification('member@example.test', 'http://localhost/verify-email/token', 1440);
            $content = file_get_contents($path);
            self::assertStringContainsString('Verify your NovaNuke email', $content);
            self::assertStringContainsString('/verify-email/token', $content);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
