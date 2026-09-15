<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Auth\AccountEmailService;
use NovaNuke\Auth\PasswordResetService;
use NovaNuke\Auth\RegistrationService;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Mail\LogMailer;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;

final class MailWorkflowIntegrationTest extends MySqlIntegrationTestCase
{
    private ?string $mailLog = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailLog = sys_get_temp_dir() . '/novanuke-mail-workflows-' . bin2hex(random_bytes(6)) . '.log';
    }

    protected function tearDown(): void
    {
        if ($this->mailLog !== null && is_file($this->mailLog)) {
            unlink($this->mailLog);
        }
        parent::tearDown();
    }

    public function testRegistrationResetAndEmailChangeProduceSingleUseHttpsLinks(): void
    {
        $settings = new SettingsRepository($this->db());
        $settings->setBoolean('users.registration_open', true, 'users');
        $settings->setBoolean('users.email_verification_required', true, 'users');
        $events = new EventDispatcher();
        $mailer = new LogMailer((string) $this->mailLog, 'testing', 'noreply@example.test', 'NovaNuke Test');
        $siteUrl = 'https://novanuke.test';

        $registration = new RegistrationService($this->db(), $settings, $mailer, $events, $siteUrl);
        self::assertTrue($registration->register('mail-member', 'member@example.test', 'Old-password-92!', 'en', 'UTC'));
        $registrationToken = $this->extractToken('#https://novanuke\.test/verify-email/([a-f0-9]{64})#');
        self::assertTrue($registration->verify($registrationToken));
        self::assertFalse($registration->verify($registrationToken));

        file_put_contents((string) $this->mailLog, '');
        $resets = new PasswordResetService($this->db(), $mailer, $siteUrl);
        $resets->request('member@example.test', '127.0.0.1');
        $resetToken = $this->extractToken('#https://novanuke\.test/reset-password/([a-f0-9]{64})\?email=#');
        self::assertTrue($resets->isValid('member@example.test', $resetToken));
        $resets->reset('member@example.test', $resetToken, 'New-password-93!');
        self::assertFalse($resets->isValid('member@example.test', $resetToken));

        $userId = (int) $this->db()->query("SELECT id FROM users WHERE email='member@example.test' LIMIT 1")->fetchColumn();
        file_put_contents((string) $this->mailLog, '');
        $emailChanges = new AccountEmailService($this->db(), $mailer, $events, $siteUrl);
        self::assertNull($emailChanges->request($userId, 'changed@example.test', 'New-password-93!'));
        $emailToken = $this->extractToken('#https://novanuke\.test/account/email/verify/([a-f0-9]{64})#');
        self::assertSame($userId, $emailChanges->confirm($emailToken));
        self::assertNull($emailChanges->confirm($emailToken));
        self::assertSame('changed@example.test', (string) $this->db()->query("SELECT email FROM users WHERE id={$userId}")->fetchColumn());
    }

    private function extractToken(string $pattern): string
    {
        $message = (string) file_get_contents((string) $this->mailLog);
        self::assertSame(1, preg_match($pattern, $message, $match), $message);
        return $match[1];
    }
}
