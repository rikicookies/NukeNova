# Updating NovaNuke

## Preserve before replacing files

- `.env`;
- `composer.lock`;
- `storage/installed.lock`;
- `storage/private/downloads/`, `storage/private/avatars/` and `storage/private/backups/`;
- user uploads and any manually installed modules/themes.

Never extract an update in a way that deletes unrelated persistent files.

## Safe update sequence

1. Create an off-server database and file backup.
2. Enable maintenance mode in `/admin/settings`.
3. Replace application files while preserving the items above.
4. Run:

```bash
composer install --no-dev --optimize-autoloader
php bin/cms migrate:status
php bin/cms migrate
php bin/cms cache:clear
php bin/cms release:check
```

5. Apply compatible updates shown in `/admin/modules` and `/admin/themes`.
6. Run `php bin/cms migrate:status` again. It succeeds only when no core/module migrations, missing migration files or module-version updates require attention.
7. Run the smoke-test list in `docs/RELEASE.md`.
8. Disable maintenance mode.

`migrate` executes core migrations only. Module migrations run through the controlled update action in `/admin/modules`, where NovaNuke also checks module and dependency versions.

If `composer.json` adds a package absent from the retained lock, update only that package first. For Phase 7C/7D:

```bash
composer update phpmailer/phpmailer
```

Do not delete `storage/installed.lock` during an update. Removing it intentionally re-enables installer routing and is not an update procedure.

## Updating to alpha.15

After copying the release, update Private Messages to 1.1.0 and both bundled themes to 1.7.0 from their administration screens. Notifications appears as an available module; install and enable it to create its table and expose `/notifications`. No core migration is added by this release.

## Updating to alpha.16

Update News to 1.4.0 and Pages to 1.2.0, then install and enable SEO 1.0.0. Confirm the Site URL under `/admin/settings`, clear caches and visit `/sitemap.xml` and `/robots.txt`. This release adds no core migration.

## Updating to alpha.17

Update News to 1.5.0 and Pages to 1.3.0, then install and enable Media 1.0.0. Confirm that PHP can write `public/uploads/`, upload a small test image at `/admin/media`, and select it from both content editors. Preserve `public/uploads/media/` during every future update and backup. This release adds no core migration.

## Updating to alpha.18

Update Media to 1.1.0, clear Twig caches, and run `php bin/cms i18n:check`. Confirm both the site-wide language and a user's language preference switch translated core/theme/Media screens. This release adds no database migration.

## Updating to beta.1

No database or bundled module migration is required. Replace application files, clear caches, run the complete test suites and inspect `php bin/cms migrate:status`. All bundled manifests now declare module API 1.0; third-party modules that omit `api_version` remain compatible as API 1.0, while unsupported API versions are rejected. Review `docs/API_STABILITY.md` before developing or updating an external module.

## Updating to beta.2

No migration or module update is required. Replace application files, run `php bin/cms cache:clear`, then execute both test suites. Production hosts should enable OPcache and keep `APP_DEBUG=false`; see `docs/PERFORMANCE.md`. Verify public menus and role-restricted blocks as part of the smoke test because their database hydration is now batched.

## Updating to rc.1

No migration or module update is required. Preserve and upload dotfiles so `public/.user.ini` and both `.htaccess` files reach the server. Clear caches, run the full release commands, then execute `php bin/cms production:check`; every `FAIL` blocks deployment while SMTP and OPcache may remain `WARN` during private testing. Follow `docs/BLUEHOST.md` for the shared-host layout.

## Updating to 0.1.0

No database migration, bundled module update or theme update is required when upgrading from rc.1. Replace the application files while preserving persistent data, clear caches and execute the complete automated and smoke-test procedures in `docs/RELEASE.md`. The stable release keeps module API 1.0 unchanged.

## Updating to 0.1.1

No migration, module update or theme update is required. This patch normalizes Windows and Unix line endings in sanitized log messages. Replace application files, clear caches and run both test suites. Module API 1.0 is unchanged.

## Updating to 0.1.2

No migration, module update or theme update is required. Account profile reads now tolerate a missing `user_profiles` row, and the next profile or avatar save recreates it through a constrained upsert. Replace application files, clear caches and run both test suites. Module API 1.0 is unchanged.

## Updating to 0.1.3

No migration, module update or theme update is required. The account profile template now safely renders every optional value even if an incomplete request supplies an empty profile or error map. Replace application files and remove compiled Twig cache files before testing `/account/profile`. Module API 1.0 is unchanged.

## Updating to 0.1.4

No migration, module update or theme update is required. General settings, account security and email-change forms now safely read empty validation maps in Twig strict mode. Replace application files, clear the application cache and restart the local PHP/Apache service before retesting. Module API 1.0 is unchanged.

## Updating to 0.2.0-alpha.1

No database migration, module update or theme update is required. This release adds `league/commonmark`; installations preserving an older `composer.lock` must run `composer update league/commonmark` once, followed by `composer install`. Clear caches, then test creating, editing and switching HTML/Markdown blocks. Existing HTML blocks retain their type and content. Module API 1.0 is unchanged.
