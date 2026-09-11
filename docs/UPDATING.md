# Updating NovaNuke

## Preserve before replacing files

- `.env`;
- `composer.lock`;
- `storage/installed.lock`;
- `storage/private/downloads/`, `storage/private/avatars/` and `storage/private/backups/`;
- user uploads and any manually installed modules/themes.

Never extract an update in a way that deletes unrelated persistent files.

## Safe update sequence

1. Create database and file backups, verify them, and copy the verified pair off-server:

```bash
php bin/cms backup:database
php bin/cms backup:files
php bin/cms backup:verify
```

2. Enable maintenance mode in `/admin/settings`.
3. Replace application files while preserving the items above.
4. Run the read-only preflight with the exact version being replaced:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.38
```

Alpha.39 supports direct preflight from Alpha.33 through Alpha.38. For an older release, follow its documented intermediate updates or perform a clean installation and controlled data migration; do not claim an untested direct upgrade.

5. Run:

```bash
composer install --no-dev --optimize-autoloader
php bin/cms migrate:status
php bin/cms migrate
php bin/cms cache:clear
php bin/cms release:check
```

6. Apply compatible updates shown in `/admin/modules` and `/admin/themes`.
7. Run `php bin/cms migrate:status` again. It succeeds only when no core/module migrations, missing migration files or module-version updates require attention.
8. Run the smoke-test list in `docs/RELEASE.md`.
9. Disable maintenance mode.

`migrate` executes core migrations only. Module migrations run through the controlled update action in `/admin/modules`, where NovaNuke also checks module and dependency versions.

If `composer.json` adds a package absent from the retained lock, update only that package first. For Phase 7C/7D:

```bash
composer update phpmailer/phpmailer
```

Do not delete `storage/installed.lock` during an update. Removing it intentionally re-enables installer routing and is not an update procedure.

`upgrade:check` never writes to the database or filesystem. It performs the same database, TAR and matched-pair integrity checks as `backup:verify`, additionally requiring both backups to be no more than 24 hours old. A `WARN` for pending migrations or module updates describes expected work; a `FAIL` must be resolved before running `migrate`.

## Recovering from a failed migration

NovaNuke stops at the first failed core or module migration and does not record that migration as completed. It does not automatically call `down()`: MySQL and MariaDB may commit DDL implicitly, so an automatic rollback cannot promise restoration of the previous schema.

1. Keep maintenance mode enabled and do not retry the migration blindly.
2. Save the exact migration name and underlying error from the console or application log.
3. Restore the database backup and file backup created together immediately before the update. Do not combine a restored database with newer application files.
4. Confirm the restored site's version and run `php bin/cms migrate:status`.
5. Correct the original cause in a disposable copy, create a fresh matched backup pair and repeat the documented update sequence.

If `migrate` reports executed migration files as missing, restore the correct release files before doing anything else. Never delete rows from `migrations` or `module_migrations` merely to silence the check.

`backup:verify` checks the newest NovaNuke SQL and TAR backups in private storage. It validates the SQL envelope and SHA-256 fingerprint, then validates TAR headers, terminator, safe regular-file paths and every manifest size/hash without extracting content. Both files must be valid and created no more than ten minutes apart. Verification proves that the generated files are internally intact; a periodic restoration test on a disposable database remains necessary.

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

## Updating to 0.2.0-alpha.2

Update Polls to 1.1.0 and Statistics to 1.2.0 from `/admin/modules`. Their idempotent migrations restore deleted default blocks in a disabled state and leave existing blocks unchanged. Clear caches, enable one restored block at a time and verify the public site. A dynamic provider exception is now redacted and logged while only the affected block is omitted. No core database migration or theme update is required; module API 1.0 is unchanged.

## Updating to 0.2.0-alpha.3

Update both bundled themes to 1.8.0 from `/admin/themes` so their layouts and published CSS render active sidebars on every public module route. Clear caches and restart PHP/Apache. This release also moves application boot inside the HTTP error boundary and fixes MIME validation warnings. No database or module migration is added; module API 1.0 remains unchanged.

## Updating to 0.2.0-alpha.4

No database, module or theme update is required from alpha.3. Menus and the mutable block-region global are now registered before any dynamic provider renders Twig, preventing the `Unable to add global blocks` LogicException. Replace application files, clear caches and restart PHP/Apache before enabling Polls or Statistics blocks. Module API 1.0 remains unchanged.

## Updating to 0.2.0-alpha.5

No database migration or module/theme update is required from alpha.4. Replace the application files, clear the Twig/application cache and restart PHP/Apache. Authorized users will then see Edit shortcuts on the four supported public detail pages; the destination Admin editors retain their existing server-side permission checks.

## Updating to 0.2.0-alpha.6

No database migration or module update is required from alpha.5. Replace the application files, clear caches and restart PHP/Apache. NovaModern is a new optional theme: install and activate version 1.0.0 from `/admin/themes`. Default and Classic remain unchanged. If the new assets are not visible, use the theme Update action once and perform a hard browser refresh.

## Updating to 0.2.0-alpha.7

No database migration or module update is required from alpha.6. After replacing files, open `/admin/themes` and run **Update** for NovaModern 1.1.0 so its corrected stylesheet is republished. Then clear application caches and perform a hard browser refresh. Responsive block order remains deferred and does not block this contrast/spacing release.

## Updating from 0.2.0-alpha.32 to 0.2.0-alpha.33

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.9.0 to 2.0.0.
3. Enable PHP's optional ZIP extension if complete Wiki archive export is required.
4. Run `php bin/cms cache:clear`.
5. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.33.md`.

No database migration, core migration, theme update or new Composer dependency is required. Folder imports create unpublished drafts and skip existing paths.

## Updating from 0.2.0-alpha.31 to 0.2.0-alpha.32

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.8.0 to 1.9.0.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.32.md`.

No database migration, core migration, theme update or new dependency is required. Existing image attachments receive Markdown snippets automatically.

## Updating from 0.2.0-alpha.30 to 0.2.0-alpha.31

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`, then replace the application files.
2. Back up the database, then open Admin → Modules and update **Wiki** from 1.7.0 to 1.8.0. Its module migration creates the attachment metadata table.
3. Confirm PHP can write `storage/private/wiki/`.
4. Run `php bin/cms cache:clear`.
5. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.31.md`.

No core migration, theme update or update to another module is required.

## Updating from 0.2.0-alpha.29 to 0.2.0-alpha.30

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.6.0 to 1.7.0.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.30.md`.

No database migration, core migration, theme update or update to another module is required. Existing colon-separated paths appear automatically in the hierarchical directory.

## Updating from 0.2.0-alpha.28 to 0.2.0-alpha.29

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Back up the database, then open Admin → Modules and update **Wiki** from 1.5.0 to 1.6.0. Its module migration adds the comments setting to Wiki pages and revision snapshots.
3. Keep **Comments 1.2.0** active only on sites that need Wiki discussions.
4. Run `php bin/cms cache:clear`.
5. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.29.md`.

No core migration, theme update, Comments update or update to other modules is required. Existing Wiki pages and revisions default to comments disabled.

## Updating from 0.2.0-alpha.27 to 0.2.0-alpha.28

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.4.0 to 1.5.0.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.28.md`.

No migration, core migration, theme update or update to other modules is required. Imported files are deliberately opened as unsaved drafts.

## Updating from 0.2.0-alpha.26 to 0.2.0-alpha.27

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.3.0 to 1.4.0.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.27.md`.

No migration, core migration, theme update or update to other modules is required. Existing Wiki revision history is immediately available for comparison.

## Updating from 0.2.0-alpha.25 to 0.2.0-alpha.26

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.2.0 to 1.3.0.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.26.md`.

No migration, reindex, core migration, theme update or update to other modules is required. When Search is active, Wiki appears automatically as a content type.

## Updating from 0.2.0-alpha.24 to 0.2.0-alpha.25

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.1.0 to 1.2.0.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.25.md`.

No migration, reindex, core migration, theme update or update to other modules is required. Existing internal Markdown links are discovered immediately.

## Updating from 0.2.0-alpha.23 to 0.2.0-alpha.24

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Open Admin → Modules and update **Wiki** from 1.0.0 to 1.1.0. This creates its revision table and records the current state of every existing Wiki page as revision 1.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.24.md`.

No core migration, theme update or update to other modules is required. Back up the database before the Wiki module migration.

## Updating from 0.2.0-alpha.22 to 0.2.0-alpha.23

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Open Admin → Modules and install/enable **Wiki 1.0.0** only on sites that need it.
3. Assign `wiki.edit` and `wiki.publish` to the desired non-super-administrator roles under Admin → Roles.
4. Run `php bin/cms cache:clear`.
5. Run the focused tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.23.md`.

Wiki owns and runs its migration during installation. Existing core and module tables require no migration, and no theme update is required.

## Updating from 0.2.0-alpha.21 to 0.2.0-alpha.22

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Run `php bin/cms cache:clear`.
3. Run the focused tests and smoke checks in `docs/RELEASE_NOTES_0.2.0-alpha.22.md`.

No database migration, module update or theme update is required. Existing VIP grants appear automatically in the enhanced Admin → Users list.

## Updating from 0.2.0-alpha.20 to 0.2.0-alpha.21

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. In Admin → Modules update **Pages** to 1.5.1, **News** to 1.8.1, **Downloads** to 1.4.1 and **Web Links** to 1.2.1.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and smoke checks in `docs/RELEASE_NOTES_0.2.0-alpha.21.md`.

No database migration or theme update is required. Public profiles reveal only active VIP status; exact expiration remains visible only in the account page and user administration.

## Updating from 0.2.0-alpha.19 to 0.2.0-alpha.20

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Run `php bin/cms migrate` to create VIP entitlements and add module/block audiences.
3. In Admin → Modules update **Pages** to 1.5.0, **News** to 1.8.0, **Downloads** to 1.4.0 and **Web Links** to 1.2.0.
4. Run `php bin/cms cache:clear`.
5. Run the tests and acceptance checks in `docs/RELEASE_NOTES_0.2.0-alpha.20.md`.

Existing modules, blocks and content remain public. No theme update is required. Create a backup before applying module migrations.

## Updating from 0.2.0-alpha.18 to 0.2.0-alpha.19

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Run `php bin/cms migrate` to add the mandatory-password-change flag.
3. Run `php bin/cms cache:clear`.
4. Run the focused tests and smoke checks in `docs/RELEASE_NOTES_0.2.0-alpha.19.md`.

No module or theme update is required. Existing accounts are not forced to change their passwords. Private-site mode remains disabled until enabled under Admin → Registration settings.

## Updating from 0.2.0-alpha.17 to 0.2.0-alpha.18

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Run `php bin/cms cache:clear`.
3. Run the focused tests and smoke checks in `docs/RELEASE_NOTES_0.2.0-alpha.18.md`.

No migration, module update or theme update is required. Optional social links are shown only when their corresponding modules are active.

## Updating from 0.2.0-alpha.15 to 0.2.0-alpha.17

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`, then replace the application files.
2. Run `php bin/cms migrate` to add optional profile website and location fields.
3. In Admin → Modules update **Private Messages** to 1.2.0, **Comments** to 1.2.0, **Notifications** to 1.1.0 and **News** to 1.7.0.
4. Install and enable **Friends** 1.0.0.
5. Run `php bin/cms cache:clear`, the automated tests and the smoke checks in `docs/RELEASE_NOTES_0.2.0-alpha.17.md`.

Existing messages and comments receive safe default formats through their module migrations. Existing users receive null website/location values. No theme update is required.

## Updating to 0.2.0-alpha.16

- Replace the application files, then update **Private Messages** to 1.2.0 in Admin → Modules.
- Existing messages are assigned `markdown`. New messages and replies offer Markdown by default or sanitized HTML.
- No core migration or theme asset update is required.

## Updating to 0.2.0-alpha.15

- Replace the application files and run `php bin/cms migrate`.
- Existing biographies are assigned `markdown`; users may select Markdown or sanitized HTML from account settings.
- No module lifecycle action or theme asset update is required.

## Updating to 0.2.0-alpha.14

- Replace the application files, then update **Comments** to 1.1.0 in Admin → Modules.
- Existing comments are assigned `markdown`. New comments, replies and edits offer Markdown by default or sanitized HTML.
- No core migration or theme asset update is required.

## Updating to 0.2.0-alpha.13

- Replace the application files, then update **Web Links** to 1.1.0 in Admin → Modules.
- Existing descriptions are assigned `html`. Administrators and submitting users may select sanitized HTML or Markdown.
- No core migration or theme asset update is required.

## Updating to 0.2.0-alpha.12

- Replace the application files, then update **Downloads** to 1.3.0 in Admin → Modules.
- Existing descriptions and requirements are assigned `html`. Editors may independently select HTML or Markdown for both fields.
- No core migration or theme asset update is required.

## Updating to 0.2.0-alpha.11

- Replace the application files, then update **News** to 1.6.0 in Admin → Modules.
- Existing summaries and article bodies are assigned `html`. Editors may independently choose HTML or Markdown for each field.
- No core migration or theme asset update is required.

## Updating to 0.2.0-alpha.10

- Replace the application files, then update **Pages** to 1.4.0 in Admin → Modules.
- Existing pages are assigned `html`, preserving their current appearance. New and edited pages can explicitly select sanitized HTML or Markdown.
- No core migration or theme asset update is required.

## Updating to 0.2.0-alpha.9

- Replace the application files and run `composer install --no-dev --optimize-autoloader` in production.
- No database migration, module update or theme asset publication is required.
- Authorized editors now get a confirmed Delete action on public News, Pages, Downloads and Web Links detail pages.

## Updating to 0.2.0-alpha.8

No migration, module lifecycle action or theme update is required from alpha.7. Replace files and clear caches. Recommended links and external downloads now open in a separate tab; local downloads are unchanged.
