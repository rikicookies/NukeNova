# Email delivery

NovaNuke supports two transports:

- `log` for Laragon/development only;
- authenticated encrypted `smtp` for production.

PHPMailer is used as the small, dedicated SMTP implementation. NovaNuke keeps message creation and the `Mailer` contract in the core, so authentication services do not depend directly on PHPMailer.

## Install the dependency

Phase 7C changes `composer.json`. After copying the new files while preserving your existing `.env` and `composer.lock`, run this once:

```bash
composer update phpmailer/phpmailer
```

This adds PHPMailer to the lock file. Afterwards normal deployments use:

```bash
composer install --no-dev --optimize-autoloader
```

## Bluehost cPanel example

Create the mailbox first, then open Bluehost **Hosting → cPanel Email → Email Accounts → Connect Devices** and use the exact manual outgoing-server values shown there.

Typical secure cPanel values are:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=example.com
MAIL_PORT=465
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD="the mailbox password"
MAIL_ENCRYPTION=ssl
MAIL_TIMEOUT=15
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="NovaNuke"
```

Replace every example. Bluehost currently recommends authenticated SSL/TLS on port 465 for cPanel email. If the account specifically provides port 587, use `MAIL_ENCRYPTION=tls` instead. Never place these values in the repository.

## Verification

1. Open `/admin/system` and confirm `Mail transport: smtp` and `SMTP configuration: Valid`.
2. Request password recovery for a controlled account.
3. Confirm receipt, sender, subject, HTML link and text-only fallback.
4. Open the link once and verify it cannot be reused.
5. Check spam delivery and configure SPF, DKIM and DMARC through the hosting/email provider.

NovaNuke never writes SMTP debug conversations, usernames or passwords to its activity log. User-facing delivery failures remain generic; detailed exceptions go only through production-safe server logging.
