# NovaNuke

NovaNuke is a lightweight, modular CMS inspired by the simplicity of PHP-Nuke and built from scratch for modern PHP.

## Current status

Phase 3A adds the first secure installation flow. The current project includes:

- PSR-4 autoloading;
- environment-based configuration;
- a small service container;
- HTTP request and response objects;
- a router with friendly parameterized URLs;
- centralized error handling and logs;
- PDO connection factory;
- a minimal migration runner;
- Twig rendering with automatic HTML escaping;
- Apache front-controller rules;
- PHPUnit unit tests.
- server requirement checks;
- a CSRF-protected web installer;
- automatic database creation when the database account permits it;
- core identity and settings migrations;
- initial roles and first Super Administrator creation;
- atomic `.env` generation and an installation lock.

Login, public registration, password recovery, authorization policies, modules, themes, blocks and content are intentionally not implemented yet.

## Requirements

- PHP 8.3 or newer;
- Composer 2;
- MySQL 8+ or a compatible MariaDB release;
- PHP extensions: PDO, PDO MySQL, JSON, Mbstring and OpenSSL.

## Local setup

```bash
composer install
composer test
composer serve
```

Open `http://127.0.0.1:8080`. When NovaNuke has not been configured, it redirects to `/install`.

Do not copy `.env.example` to `.env` before testing the web installer. The installer generates `.env` from the validated form.

### Laragon defaults

Common Laragon database values are:

- host: `127.0.0.1`;
- port: `3306`;
- username: `root`;
- password: empty;
- database: `novanuke`.

The database account must be allowed to create a database. Alternatively, create the database manually first.

Do not expose the project root to the web. The document root must be the `public/` directory.

## Security baseline

- Debug output is disabled by default in production.
- Twig escapes HTML output automatically.
- PDO emulated prepared statements are disabled.
- Secrets belong in `.env`, which is excluded from Git.
- The public entry point is isolated in `public/`.

The installer uses CSRF protection, HTTP-only session cookies, strict session mode, server-side validation, prepared statements and `password_hash()`.

After installation, `storage/installed.lock` prevents installer routes from loading. Never remove this file on a production installation. Removing it is a deliberate manual recovery action, not a normal reinstall method.

Authentication, authorization and rate limiting continue in the next Phase 3 delivery.

## Updating an installed development copy

After replacing project files, keep the existing `.env` and `storage/installed.lock`, then run:

```bash
composer install
composer migrate
composer test
```

Phase 3B provides `/login`, POST-only `/logout`, a protected `/admin` dashboard, session regeneration, suspended-account enforcement, login history and basic login throttling.

## Local password recovery

Phase 3C-A adds password recovery through a development mail log. In a local installation, set:

```dotenv
APP_ENV=development
APP_DEBUG=true
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@localhost
MAIL_FROM_NAME=NovaNuke
```

Then request a reset at `/forgot-password` and open the newest link in `storage/logs/mail.log`.

Reset tokens expire after 60 minutes, are stored only as SHA-256 hashes, can be used once and are replaced when a new reset is requested. A successful password change increments the user's authentication version, invalidating all older sessions.

The log mailer refuses to operate when `APP_ENV=production`. Before publishing NovaNuke, configure a real SMTP driver and remove `storage/logs/mail.log` because development recovery links are secrets while active.

## Registration and email verification

Phase 3C-B keeps public registration closed by default. A Super Administrator can change it at `/admin/settings/users` and independently choose whether new accounts require email verification.

When verification is required, new users receive the `Member` role and remain in `pending_verification` status. The one-time verification link is written to `storage/logs/mail.log` during local development and expires after 24 hours. Opening it activates the account. Reopening a consumed link produces an expired-link screen.

Disabling verification affects only future registrations; it does not automatically activate accounts already awaiting verification.

## Roles, permissions and audit history

Phase 3C-C adds server-enforced permissions, user role assignment, suspension/reactivation, persistent database rate limits and administrative activity logs.

- `/admin/users` manages account status and role assignments.
- `/admin/roles` displays roles and edits permission assignments.
- `/admin/logs` displays the latest 200 administrative audit events.
- `docs/AUTHORIZATION.md` documents the internal authorization contract.

NovaNuke prevents users from changing their own roles/status in the panel and prevents removal or suspension of the final active Super Administrator.

## Tests

Run:

```bash
composer test
```

No test result should be reported as passing unless it was run under PHP 8.3+.
