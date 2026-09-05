<?php

declare(strict_types=1);

namespace NovaNuke\Core\Mail;

use RuntimeException;

final class LogMailer implements Mailer
{
    public function __construct(
        private readonly string $path,
        private readonly string $environment,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {
    }

    public function sendPasswordReset(string $recipient, string $resetUrl, int $expiresInMinutes): void
    {
        $this->write(
            $recipient,
            'Reset your NovaNuke password',
            $resetUrl,
            $expiresInMinutes,
            'If you did not request this reset, ignore this message.',
        );
    }

    public function sendEmailVerification(string $recipient, string $verificationUrl, int $expiresInMinutes): void
    {
        $this->write(
            $recipient,
            'Verify your NovaNuke email',
            $verificationUrl,
            $expiresInMinutes,
            'If you did not create this account, ignore this message.',
        );
    }

    public function sendEmailChangeVerification(string $recipient, string $verificationUrl, int $expiresInMinutes): void
    {
        $this->write(
            $recipient,
            'Confirm your new NovaNuke email',
            $verificationUrl,
            $expiresInMinutes,
            'If you did not request this email change, ignore this message and your current address will remain active.',
        );
    }

    private function singleLine(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }

    private function write(
        string $recipient,
        string $subject,
        string $url,
        int $expiresInMinutes,
        string $footer,
    ): void {
        if ($this->environment === 'production') {
            throw new RuntimeException('The log mailer is disabled in production. Configure SMTP first.');
        }

        $message = implode(PHP_EOL, [
            '------------------------------------------------------------',
            'Date: ' . gmdate('c'),
            'From: ' . $this->singleLine($this->fromName) . ' <' . $this->singleLine($this->fromAddress) . '>',
            'To: ' . $this->singleLine($recipient),
            'Subject: ' . $this->singleLine($subject),
            '',
            "Open this one-time link within {$expiresInMinutes} minutes:",
            $url,
            $footer,
            '------------------------------------------------------------',
            '',
        ]);

        if (file_put_contents($this->path, $message, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('The development email could not be written.');
        }
    }
}
