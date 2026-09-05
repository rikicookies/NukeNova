# NovaNuke 0.1.0

NovaNuke 0.1.0 is the first stable release of the lightweight modular CMS. It preserves the approachable module, theme and block model of classic portal software while using PHP 8.3+, PDO, Twig, explicit authorization and modern request security.

## Included

- Web installer and locked post-install state.
- Authentication, recovery, email verification, profiles, roles and permissions.
- Administrative dashboard, settings, logs and system diagnostics.
- Modules, migrations, dependency checks, API 1.0 and lightweight events.
- Twig themes, view overrides, menus, positions and sanitized blocks.
- News, Comments, Pages, Downloads, Search, Private Messages, Polls, Web Links and Statistics.
- Optional Media, Notifications and SEO modules.
- Maintenance mode, backups, cache management and production audits.
- English and Spanish catalogues.

NovaNuke 0.1.0 does not include a forum and does not permit PHP uploads through the administration panel.

## Upgrade from rc.1

This release adds no migration and changes no bundled module or theme version. Preserve `.env`, `composer.lock`, `storage/installed.lock`, `storage/private/`, user uploads and manually installed extensions. Replace application files, clear caches and follow `docs/UPDATING.md`.

## Production acceptance

Before opening a public site, run every command in `docs/RELEASE.md`, resolve every required failure from `production:check`, verify HTTPS and secure cookies, and test a complete restore from an off-server backup. SMTP is required before relying on email verification or password recovery.

## Compatibility policy

The documented module API 1.0 is stable throughout the 0.1 release line. Patch releases may fix defects and add optional behavior but will not intentionally break that public contract. Internal classes not identified in `docs/API_STABILITY.md` are not part of the extension API.

## Known operational limits

- Module and theme packages are copied to the server manually; the CMS does not download executable updates.
- MySQL/MariaDB provides search; no external search service is included.
- Guest poll and analytics controls reduce obvious duplicates without claiming perfect identification.
- Development mail logging is not a substitute for production SMTP.
