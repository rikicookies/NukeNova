# Release Candidate acceptance

A NovaNuke Release Candidate is feature-frozen. RC work is limited to defects, security/reliability corrections, packaging, documentation and acceptance failures.

Passing PHPUnit alone does not qualify a release as RC-ready.

## RC.1 acceptance focus

RC.1 must be tested from the packaged archive against a pre-created empty database, then on the intended Bluehost/shared-hosting environment. This specifically validates the installer path where the database exists but the application user may not have permission to create databases.

## 1. Source package

Run against the clean release source before installation:

```bash
composer install
composer check:rc-source
composer test:checkpoint
```

Required outcome:

- no `.env`;
- no `storage/installed.lock`;
- no runtime log/cache/session/backup artifacts, public uploads or private user files;
- no environment variants such as `.env.production` and no old SQL/TAR/ZIP release artifacts at package root;
- release/version metadata matches the package;
- `composer.lock` is present so dependency installation is reproducible;
- distribution smoke/checklist passes;
- complete unit + isolated integration suites pass.
- the integration suite includes an isolated fresh-installer pass that verifies version recording, Super Administrator creation, migration completion, storage provisioning, and refusal of a non-empty database.

## 2. Fresh installation

Follow `docs/CLEAN_INSTALL_CHECKLIST.md` using a new directory and empty disposable database.

Required outcome:

- `composer check:install` passes before installation;
- web installer completes without manual table/config edits;
- installer becomes unavailable afterward;
- `php bin/cms migrate:status` reports zero pending/missing migrations, zero running/dirty recovery operations, and zero module updates;
- `composer check:site` passes after installation.

Repeat the final packaged RC clean-install pass at least once from a newly extracted archive.

## 3. Existing-site upgrade

Use a disposable copy of a real older installation.

Required sequence:

```bash
php bin/cms backup:create
php bin/cms backup:verify
php bin/cms upgrade:check --from=CURRENT_INSTALLED_VERSION
php bin/cms migrate
php bin/cms migrate:status
php bin/cms upgrade:complete --from=CURRENT_INSTALLED_VERSION
composer check:site
```

Required outcome:

- source version matches recorded version;
- verified database/file backups are paired;
- every required migration completes;
- upgrade completion records the new Core version;
- no migration/module update or recovery operation remains;
- Membership and optional Payment health checks pass.

Never edit migration rows or `system.core_version` manually to force acceptance.

## 4. Backup and restore

A generated backup is not sufficient proof by itself.

Required outcome:

- `backup:verify` passes and confirms one explicit matched backup-set ID;
- `php bin/cms backup:restore-check` passes, including disposable file extraction **and a real SQL import** when a disposable MySQL runner is configured;
- if automated SQL restore credentials are unavailable, the gate must say `MANUAL REQUIRED / NOT VERIFIED` and acceptance stays open until a manual import is recorded;
- database backup restores into a disposable database;
- private file backup restores into a disposable application copy; `backup:restore-files` verifies the archive first and only extracts into an empty destination;
- restored copy boots with the matching application release;
- authenticated users/content/private files expected from the backup are present.

NovaNuke intentionally does not provide an in-place/overwrite file restore command. Restore into an empty disposable directory, inspect it, then follow the documented recovery procedure. NovaNuke never creates the target database; the operator explicitly provisions the empty disposable database. The application may import/clean it only when acceptance credentials are configured.

Do not test restore for the first time on production.

## 5. Security and permissions

Run:

```bash
php bin/cms security:audit
```

Manually verify:

- unauthorized Admin routes are denied;
- state-changing forms reject invalid CSRF;
- private storage is not web-accessible;
- uploads cannot execute server-side code;
- production errors do not reveal stack traces, paths or secrets;
- `.env` and backup files are not public;
- account recovery/verification tokens remain single-use.

## 6. Email / SMTP

First validate the selected transport without sending mail:

```bash
php bin/cms mail:check
php bin/cms production:check
php bin/cms mail:acceptance
```

`mail:check` validates transport, sender and SMTP configuration structure without opening a delivery connection or exposing the configured password. `production:check` separately requires SMTP; `MAIL_MAILER=log` is development/test only. `mail:acceptance` remains non-zero and reports `MANUAL REQUIRED / NOT VERIFIED` until the real delivery workflows below have been completed and recorded.

On a disposable production-like environment with SMTP configured:

- registration verification arrives;
- password reset arrives;
- email-change verification arrives;
- links use the expected HTTPS site URL;
- one-time tokens cannot be reused;
- mail credentials never appear in rendered pages or sanitized logs.

After each successful real workflow, record the operator acceptance:

```bash
php bin/cms mail:acceptance --record=registration-verification
php bin/cms mail:acceptance --record=password-reset
php bin/cms mail:acceptance --record=email-change
php bin/cms mail:acceptance
```

The acceptance is invalidated by SMTP identity/credential/transport changes or an effective site-URL change. Log mail can never record production acceptance.

## 7. Production configuration

On the intended production-like host run:

```bash
composer check:release
```

Every required failure blocks deployment. Optional recommendations such as OPcache may remain warnings only when documented.

Confirm:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- HTTPS site URL;
- Secure session cookies;
- production security headers;
- document root is `public/`;
- required runtime directories are writable without making the project world-writable.

## 8. Bundled modules and themes

This is a functional regression pass, not the deferred visual-polish project.

Before the manual theme regression run:

```bash
php bin/cms theme:check
```

Required outcome:

- bundled theme manifests, declared layouts, screenshots/assets and CMS compatibility validate;
- bundled module manifests validate;
- install/update/enable/disable lifecycle works;
- enabled modules expose their expected public/Admin routes;
- current active theme survives upgrade;
- switching bundled themes does not produce a server error;
- no module has a pending migration after acceptance.

Known visual inconsistencies that do not break behavior should be recorded for the post-roadmap polish pass instead of expanding RC scope.

## 9. Consolidated deployment preflight

On the production-like RC target run:

```bash
php bin/cms rc:deployment
```

This aggregates installed-site health, a fresh verified backup pair + disposable file restore, production configuration, authorization, membership, payment, mail, bundled-theme and deployment-secret checks. Optional hardening recommendations remain warnings and do not hide required failures.

## 10. Acceptance record

Record:

- NovaNuke version and package hash;
- PHP version;
- MySQL/MariaDB version;
- web server/environment;
- unit/integration result;
- clean-install result;
- upgrade source/result;
- backup/restore result;
- SMTP result;
- security audit;
- `check:site`;
- production `check:release`;
- every accepted warning or known issue.

The first Release Candidate can be tagged only when there is no undocumented manual workaround and no unresolved release-blocking failure.
