# NovaNuke

NovaNuke is a lightweight modular CMS with an old-school spirit, written from scratch for PHP 8.3+.

Current development release: **0.2.0-alpha.1**. It begins the next feature cycle with selectable sanitized HTML or Markdown block content. Use 0.1.4 for the stable line until this alpha completes acceptance testing.

## Included in 0.1.0

- secure web installer and Super Administrator creation;
- registration, authentication, recovery, email verification, profiles, roles and permissions;
- central administration, activity logs and system diagnostics;
- manually installed modules with manifests, migrations, dependencies and hooks;
- Twig themes, overrides, positions, sanitized blocks and hierarchical menus;
- News, Comments, Pages, Media, Downloads, Search, Private Messages, Notifications, Polls, Web Links, Statistics and SEO;
- friendly URLs, PDO prepared statements, CSRF, rate limits and output escaping;
- encrypted SMTP or development mail logging;
- maintenance mode, private database/file backups and safe cache commands.
- permission-aware administrative dashboard with recent users, content, moderation, activity and module health.
- protected general settings for identity, locale, dates, pagination, homepage and maintenance mode.
- namespaced JSON translations with English, Spanish and module/theme language packs.
- public member profiles, per-user locale/timezone, private avatar storage and authenticated password changes.
- recent sign-in history and password-confirmed, rate-limited account anonymization.
- verified email changes that preserve the current address until confirmation and invalidate existing sessions.
- privacy-preserving recovery for expired registration-verification links.
- typed, privacy-minimal authentication hooks for modules.
- isolated MySQL/MariaDB integration tests for authentication, recovery and module lifecycle.

NovaNuke does not include a forum and never accepts executable PHP through the administration panel.

## Requirements

- PHP 8.3 or newer;
- Composer 2;
- MySQL 8+ or a compatible MariaDB version;
- PDO, PDO MySQL, JSON, Mbstring, OpenSSL, Fileinfo and DOM extensions;
- Apache with `mod_rewrite`, or an equivalent Nginx configuration.

The server document root must point to `public/`.

## Laragon quick start

```bash
composer install
composer test
php bin/cms release:check
composer test:integration
```

Create a Laragon site whose document root is `C:\\dev\\www\\novanuke\\public`, then open its URL. An unconfigured copy redirects to `/install`.

Common local database values are host `127.0.0.1`, port `3306`, username `root`, empty password and database `novanuke`.

Do not create `.env` manually before testing the installer. The installer writes it atomically and creates `storage/installed.lock` when installation finishes.

See [docs/INSTALLATION.md](docs/INSTALLATION.md) for complete instructions.

## Updating an existing copy

Preserve `.env`, `composer.lock`, `storage/installed.lock` and everything under `storage/private/`. Then follow [docs/UPDATING.md](docs/UPDATING.md).

```bash
composer install
php bin/cms migrate:status
php bin/cms migrate
composer test
php bin/cms cache:clear
php bin/cms release:check
```

Phase 7C added PHPMailer. If it is not yet present in your lock file, run `composer update phpmailer/phpmailer` once before the standard commands.

NovaNuke 0.2.0-alpha.1 adds CommonMark. Existing installations that preserve an older lock file must run `composer update league/commonmark` once.

## Useful CLI commands

```bash
php bin/cms migrate
php bin/cms migrate:status
php bin/cms backup:database
php bin/cms backup:files
php bin/cms cache:status
php bin/cms cache:clear
php bin/cms maintenance:prune --dry-run
php bin/cms maintenance:prune
php bin/cms downloads:orphans
php bin/cms security:audit
php bin/cms release:check
```

## Local email

Use `APP_ENV=development` and `MAIL_MAILER=log`. Recovery and verification links are written to `storage/logs/mail.log`. Those links are secrets while active.

For production SMTP and Bluehost guidance, see [docs/MAIL.md](docs/MAIL.md).

## Security and deployment

- [Production deployment](docs/PRODUCTION.md)
- [Security checklist](docs/SECURITY_CHECKLIST.md)
- [Account security and lifecycle](docs/ACCOUNT_SECURITY.md)
- [Secure email changes](docs/EMAIL_CHANGE.md)
- [Email verification recovery](docs/EMAIL_VERIFICATION.md)
- [Authentication events](docs/AUTH_EVENTS.md)
- [Backups](docs/BACKUPS.md)
- [Scheduled maintenance](docs/MAINTENANCE.md)
- [Recovery](docs/RECOVERY.md)
- [Release verification](docs/RELEASE.md)
- [Unit and integration testing](docs/TESTING.md)
- [In-site notifications](docs/NOTIFICATIONS.md)
- [SEO and sitemap](docs/SEO.md)

Detailed module and theme contracts are documented under `docs/`, including `MODULES.md`, `THEMES.md`, `BLOCKS.md` and `MENUS.md`.

The permission-aware dashboard data and extension behavior are documented in [docs/ADMIN_DASHBOARD.md](docs/ADMIN_DASHBOARD.md).
General site configuration and the boundary between database settings and `.env` secrets are documented in [docs/SETTINGS.md](docs/SETTINGS.md).
Translation catalogues and extension conventions are documented in [docs/INTERNATIONALIZATION.md](docs/INTERNATIONALIZATION.md).
Member profile privacy, avatars and account preferences are documented in [docs/PROFILES.md](docs/PROFILES.md).

## Tests

```bash
composer test
```

For the isolated Laragon database suite, configure `.env.testing` and run `composer test:integration`. See [docs/TESTING.md](docs/TESTING.md).

Never report a test as passing unless it was actually executed under PHP 8.3 or newer. This package remains alpha until its installer, update, permissions, SMTP and restore smoke-test matrix has been completed on the target environment.
