# Recovery

## Site returns an internal error

1. Leave `APP_DEBUG=false` on a public server.
2. Copy the reference shown on the error page.
3. Find that reference in `storage/logs/novanuke.log`.
4. Check PHP version/extensions, database availability, writable storage and recent file changes.
5. Do not paste `.env`, SMTP conversations, reset links or full production logs into a public ticket.

NovaNuke redacts common credential, authorization and token patterns from exception logs, but logs must still be treated as private.

## Locked out during maintenance

Login, password recovery and administrative routes remain available. Sign in through `/login`, open `/admin/settings` and disable maintenance. Do not delete the installation lock.

## Lost administrator access

Use normal password recovery after confirming SMTP delivery. If email is unavailable, restore access through a controlled database recovery performed by the server owner; never add a public bypass route or weaken core authorization.

## Damaged update

1. Keep maintenance enabled.
2. Restore the exact prior application files.
3. Restore the matching database backup when migrations changed schema/data.
4. Preserve private downloads and `.env`.
5. Run `composer install`, `php bin/cms cache:clear` and `php bin/cms release:check`.

## Installer unexpectedly appears

Stop and restore `storage/installed.lock` from a trusted backup. Verify `.env` and the database are intact. Do not submit the installer against an existing production database.
