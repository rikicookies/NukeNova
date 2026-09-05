<?php

declare(strict_types=1);

namespace NovaNuke\Core\Mail;

interface Mailer
{
    public function sendPasswordReset(string $recipient, string $resetUrl, int $expiresInMinutes): void;
}
