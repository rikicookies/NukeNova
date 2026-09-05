# Installation

## Before starting

Use PHP 8.3+, Composer 2 and MySQL/MariaDB. The web document root must be NovaNuke's `public/` directory. Internal source, `.env`, logs and private files must never be directly served.

## Laragon

1. Extract NovaNuke to a dedicated directory such as `C:\\dev\\www\\novanuke`.
2. Open a terminal in that directory and run `composer install`.
3. Configure the Laragon virtual host document root as `C:\\dev\\www\\novanuke\\public`.
4. Ensure Apache rewrite support is enabled.
5. Open the generated local URL and follow `/install`.
6. Resolve any missing extensions or unwritable storage directories.
7. Enter the database and first Super Administrator values.
8. Confirm `storage/installed.lock` exists and `/install` no longer loads.
9. Run `composer test` and `php bin/cms release:check`.

Typical Laragon MySQL values are `127.0.0.1`, port `3306`, user `root`, empty password and database `novanuke`.

Do not copy `.env.example` to `.env` before a fresh installer test. The installer generates a unique application key and writes credentials atomically.

## Apache/shared hosting

Install Composer dependencies locally if the host does not provide Composer, then transfer the complete application including `vendor/`. Prefer a Bluehost domain or subdomain whose document root can be assigned to `/home/account/novanuke/public`.

Keep the application itself outside `public_html` whenever possible. Do not expose the project root as a workaround for an inflexible hosting plan.

## Nginx

Use the `try_files` and PHP-FPM example in `docs/PRODUCTION.md`. `.htaccess` applies only to Apache.

## First checks

- Sign in and open `/admin/system`.
- Confirm the authorization audit passes.
- Install/enable required modules and select the active theme.
- Keep registration closed until email delivery and moderation settings are ready.
- Create the first backup and test restoring it on another database.
