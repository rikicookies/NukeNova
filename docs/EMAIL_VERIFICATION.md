# Email verification recovery

Pending members can request a fresh verification link at `/resend-verification`, even when public registration has subsequently been closed or the site is in maintenance mode.

The endpoint validates CSRF, accepts only syntactically valid addresses and limits requests to three per hour for each email/IP combination. Its success message is deliberately identical whether or not a pending account exists, preventing account enumeration through the interface.

For an existing `pending_verification` account, NovaNuke removes every older verification token, stores only the SHA-256 hash of a new cryptographically random token and sends a link valid for 24 hours. Active, suspended, deleted and unknown accounts receive no message.

During local development with `MAIL_MAILER=log`, inspect `storage/logs/mail.log`. In production the configured SMTP transport is used.
