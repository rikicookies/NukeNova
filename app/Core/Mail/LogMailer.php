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
        if ($this->environment === 'production') {
            throw new RuntimeException('The log mailer is disabled in production. Configure SMTP first.');
        }

        $recipient = $this->singleLine($recipient);
        $fromAddress = $this->singleLine($this->fromAddress);
        $fromName = $this->singleLine($this->fromName);
        $message = implode(PHP_EOL, [
            '------------------------------------------------------------',
            'Date: ' . gmdate('c'),
            "From: {$fromName} <{$fromAddress}>",
            "To: {$recipient}",
            'Subject: Reset your NovaNuke password',
            '',
            "Open this one-time link within {$expiresInMinutes} minutes:",
            $resetUrl,
            'If you did not request this reset, ignore this message.',
            '------------------------------------------------------------',
            '',
        ]);

        if (file_put_contents($this->path, $message, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('The development email could not be written.');
        }
    }

    private function singleLine(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }
}
