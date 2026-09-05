<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Auth\PasswordResetService;
use NovaNuke\Core\Mail\LogMailer;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use RuntimeException;

final class PasswordResetIntegrationTest extends MySqlIntegrationTestCase
{
    private ?string $mailLog = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailLog = sys_get_temp_dir() . '/novanuke-mail-' . bin2hex(random_bytes(6)) . '.log';
    }

    protected function tearDown(): void
    {
        if ($this->mailLog !== null && is_file($this->mailLog)) unlink($this->mailLog);
        parent::tearDown();
    }

    public function testResetTokenIsHashedSingleUseAndInvalidatesPendingEmailChanges(): void
    {
        $originalHash = password_hash('Old-password-92!', PASSWORD_DEFAULT);
        $insert = $this->db()->prepare(
            'INSERT INTO users (username,email,password_hash,status,email_verified_at,created_at,updated_at) '
            . 'VALUES (:username,:email,:password,:status,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $insert->execute([
            'username' => 'reset-member',
            'email' => 'reset@example.test',
            'password' => $originalHash,
            'status' => 'active',
        ]);
        $userId = (int) $this->db()->lastInsertId();
        $this->db()->prepare(
            'INSERT INTO email_change_tokens (user_id,pending_email,token_hash,expires_at,created_at) '
            . 'VALUES (:user,:email,:hash,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 MINUTE),UTC_TIMESTAMP())'
        )->execute(['user' => $userId, 'email' => 'new@example.test', 'hash' => hash('sha256', 'pending')]);

        $service = new PasswordResetService(
            $this->db(),
            new LogMailer((string) $this->mailLog, 'testing', 'noreply@example.test', 'NovaNuke Test'),
            'https://novanuke.test',
        );
        $service->request('RESET@example.test', '127.0.0.1');
        $message = (string) file_get_contents((string) $this->mailLog);
        self::assertSame(1, preg_match('#/reset-password/([a-f0-9]{64})\?email=#', $message, $match));
        $token = $match[1];

        $stored = (string) $this->db()->query('SELECT token_hash FROM password_reset_tokens')->fetchColumn();
        self::assertNotSame($token, $stored);
        self::assertSame(hash('sha256', $token), $stored);
        self::assertTrue($service->isValid('reset@example.test', $token));

        $service->reset('reset@example.test', $token, 'New-password-93!');
        $user = $this->db()->query("SELECT password_hash,auth_version FROM users WHERE id={$userId}")->fetch();
        self::assertTrue(password_verify('New-password-93!', (string) $user['password_hash']));
        self::assertSame(2, (int) $user['auth_version']);
        self::assertFalse($service->isValid('reset@example.test', $token));
        self::assertSame(0, (int) $this->db()->query('SELECT COUNT(*) FROM email_change_tokens')->fetchColumn());

        $this->expectException(RuntimeException::class);
        $service->reset('reset@example.test', $token, 'Another-password-94!');
    }
}
