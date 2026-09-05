# Secure email changes

Signed-in users can open `/account/email` from Account security. A change requires a valid CSRF token, the current password and an unused valid email address. Requests are limited to three per hour per account.

NovaNuke keeps the current address active while a hashed, one-time confirmation token is pending. The confirmation link is delivered only to the proposed address, expires after 60 minutes and can be used once. A newer request invalidates every older pending request for that user.

Changing or resetting the account password also cancels every pending email-change link.

After confirmation NovaNuke:

- rechecks the database uniqueness constraint;
- marks the new address verified;
- increments `auth_version` to close existing sessions;
- records security activity without storing either address in the log;
- dispatches `user.email_changed` with only the numeric user ID.

With `MAIL_MAILER=log` in development, the link is written to `storage/logs/mail.log`. Production continues to require SMTP.
