<?php

declare(strict_types=1);

namespace NovaNuke\Core\Mail;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class SmtpMailer implements Mailer
{
    public function __construct(private readonly SmtpConfiguration $configuration)
    {
    }

    public function sendPasswordReset(string $recipient, string $resetUrl, int $expiresInMinutes): void
    {
        $this->send(
            $recipient,
            'Reset your NovaNuke password',
            'Password reset',
            "Open the link below within {$expiresInMinutes} minutes to choose a new password.",
            $resetUrl,
            'If you did not request this reset, ignore this message.',
        );
    }

    public function sendEmailVerification(string $recipient, string $verificationUrl, int $expiresInMinutes): void
    {
        $this->send(
            $recipient,
            'Verify your NovaNuke email',
            'Email verification',
            "Open the link below within {$expiresInMinutes} minutes to verify your email address.",
            $verificationUrl,
            'If you did not create this account, ignore this message.',
        );
    }

    public function sendEmailChangeVerification(string $recipient, string $verificationUrl, int $expiresInMinutes): void
    {
        $this->send(
            $recipient,
            'Confirm your new NovaNuke email',
            'Confirm email change',
            "Open the link below within {$expiresInMinutes} minutes to make this your account email address.",
            $verificationUrl,
            'If you did not request this change, ignore this message and your current address will remain active.',
        );
    }

    private function send(string $recipient, string $subject, string $heading, string $intro, string $url, string $footer): void
    {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('The recipient email address is invalid.');
        if (! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null
            || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            throw new RuntimeException('The email action URL is invalid.');
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->configuration->host;
            $mail->Port = $this->configuration->port;
            $mail->SMTPAuth = true;
            $mail->Username = $this->configuration->username;
            $mail->Password = $this->configuration->password;
            $mail->SMTPSecure = $this->configuration->encryption === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAutoTLS = false;
            $mail->Timeout = $this->configuration->timeout;
            $mail->SMTPDebug = 0;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($this->configuration->fromAddress, $this->configuration->fromName);
            $mail->addAddress($recipient);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $safeHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeIntro = htmlspecialchars($intro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeFooter = htmlspecialchars($footer, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $mail->Body = "<h1>{$safeHeading}</h1><p>{$safeIntro}</p><p><a href=\"{$safeUrl}\">Continue</a></p><p>{$safeFooter}</p>";
            $mail->AltBody = "{$heading}\n\n{$intro}\n{$url}\n\n{$footer}";
            $mail->send();
        } catch (PHPMailerException $error) {
            throw new RuntimeException('The SMTP server could not deliver the message.', previous: $error);
        }
    }
}
