# NovaNuke 0.4.0-rc.4 — Release Reliability & Recovery

RC.4 is a reliability-only release candidate. It is being completed in six audited batches; it is not ready for promotion until every authorized finding has a tested disposition.

## Batch 1 — recoverable migrations (NN-MIG-01)

- Core and module migrations share a database-scoped MySQL/MariaDB advisory lock, preventing concurrent schema runners without shell access.
- The additive `migration_operations` control table records `running`, `dirty`, and `completed`, the direction, attempt count, error summary, timestamps, and migration-file SHA-256.
- Existing `migrations` and `module_migrations` rows remain authoritative and are neither renamed nor backfilled.
- Every bundled migration now has verifiable applied/rolled-back postconditions and repeat-safe schema/data steps.
- `php bin/cms migrate:recover` reconciles Core; `--module=SLUG` covers module install/update and data-deleting uninstall recovery.
- Ordinary Core/module migration and module-uninstall paths now hard-stop when any operation is `running` or `dirty`; only the explicit recovery command may reconcile it.
- Upgrade, deployment health, and migration status fail while an interrupted operation remains unresolved.

No numbered application migration is added. NovaNuke creates the additive control table when the migration repository is initialized, before executing DDL. Existing sites create it automatically on the first migration/recovery path; read-only status remains non-mutating and prior history remains unchanged.

## Batch 1 acceptance

Run the migration recovery integration test against disposable MySQL/MariaDB, then the checkpoint suite:

```bash
NOVANUKE_RUN_INTEGRATION=1 php vendor/bin/phpunit --filter MigrationRecoveryIntegrationTest tests/Integration/MigrationRecoveryIntegrationTest.php
composer test:checkpoint
php bin/cms migrate:status
```

The integration suite injects failures after committed DDL but before history, between DDL statements, during a fresh install, during module install, and during module uninstall. It also verifies clean installer retry, changed-file rejection, legacy-history compatibility, and concurrent-runner exclusion.


## Batch 2 — backup consistency and restore verification (NN-BACKUP-01)

- Database export now runs under a MySQL/MariaDB `REPEATABLE READ` consistent snapshot for InnoDB tables; mixed/non-transactional engines are recorded and cannot silently satisfy the RC consistency gate.
- SQL and TAR artifacts carry a shared explicit backup-set ID. Timestamp proximity is no longer treated as pairing proof.
- `php bin/cms backup:create` creates the preferred coordinated backup set. Existing standalone DB/files commands remain available.
- `backup:restore-check` distinguishes file integrity from restorability. With an operator-provisioned empty disposable MySQL DSN it imports the SQL, verifies restored tables and migration history, and cleans the target afterward.
- Without a disposable SQL runner, backup recovery and therefore `rc:deployment` report `MANUAL REQUIRED / NOT VERIFIED` instead of PASS.
- No `mysqldump`, shell access, or `CREATE DATABASE` permission is required.
- No numbered application migration is introduced by Batch 2.

### Batch 2 acceptance

```bash
php bin/cms backup:create
php bin/cms backup:verify
php bin/cms backup:restore-check
composer test:checkpoint
composer test:integration
php bin/cms rc:deployment
```

For automated SQL restore acceptance, configure `NOVANUKE_BACKUP_VERIFY_DSN`, `NOVANUKE_BACKUP_VERIFY_USERNAME`, and `NOVANUKE_BACKUP_VERIFY_PASSWORD` for an empty disposable database created outside NovaNuke.

## Batch 3 — production mail readiness and delivery acceptance (NN-MAIL-01)

- `mail:check` remains a structural configuration check. `MAIL_MAILER=log` is still valid for local development and automated tests.
- `production:check` now requires the SMTP transport and a structurally valid SMTP configuration. A production environment using `MAIL_MAILER=log` fails readiness.
- `rc:deployment` separately requires durable delivery acceptance for registration verification, password reset, and email-change verification. Structurally valid SMTP is not treated as proof of delivery.
- `php bin/cms mail:acceptance` reports the three workflow states. Until all three have been exercised on a production-like host, the command reports `MANUAL REQUIRED / NOT VERIFIED` and exits non-zero.
- After completing each real workflow, record it explicitly with `--record=registration-verification`, `--record=password-reset`, or `--record=email-change`.
- Acceptance evidence is bound to the current SMTP configuration and effective site URL. Changing the transport, SMTP host/port/user/password/encryption/from identity, or site URL invalidates previous evidence automatically.
- Acceptance state is kept under `storage/private/mail-acceptance.json` with restrictive permissions and is not part of the source release.
- No database migration is introduced by Batch 3.

### Batch 3 acceptance

On the production-like host, configure SMTP first and run:

```bash
php bin/cms mail:check
php bin/cms production:check
php bin/cms mail:acceptance
```

Then exercise the actual user-facing workflows and confirm delivery, HTTPS links, expected recipient/sender, and one-time token behavior. Only after each workflow succeeds, record the corresponding acceptance:

```bash
php bin/cms mail:acceptance --record=registration-verification
php bin/cms mail:acceptance --record=password-reset
php bin/cms mail:acceptance --record=email-change
php bin/cms mail:acceptance
php bin/cms rc:deployment
```

Recording acceptance is an operator attestation after the real workflow test; it does not send a synthetic message and cannot turn `MAIL_MAILER=log` into an accepted production transport.


## Batch 4 — atomic theme asset publication (NN-THEME-01)

- Theme assets are fully validated before the active public tree is touched. Symlinks, unsupported extensions, missing/unsupported screenshots, and unreadable files abort before publication.
- Every source file is SHA-256 hashed, copied into a sibling staging directory, and verified again from staging before the swap.
- Replacement uses same-parent directory renames: the current tree is moved to a temporary backup, the verified staging tree is renamed into place, and the previous tree is restored if the second rename fails.
- Copy/staging failures therefore leave the active tree untouched. Temporary staging/backup directories are cleaned after success or recoverable failure.
- If an exceptional filesystem failure prevents rollback itself, the backup directory is deliberately preserved instead of deleting the last known-good asset copy.
- No database migration, theme manifest/version change, or theme redesign is introduced by Batch 4.

### Batch 4 acceptance

```bash
php vendor/bin/phpunit --filter ThemeAssetPublisherTest tests/Unit/ThemeAssetPublisherTest.php
NOVANUKE_RUN_INTEGRATION=1 php vendor/bin/phpunit --filter ThemeLifecycleIntegrationTest tests/Integration/ThemeLifecycleIntegrationTest.php
php bin/cms theme:check
composer test:checkpoint
```

The unit suite injects a copy failure after staging has begun and a swap rename failure after the old tree has been moved. Both cases must retain the previous active CSS and leave no disposable staging/backup directories behind.

## Batch 5 — NN-SEC-01

- Centralized comment parent-target authorization through `CommentTargetAccessGuard`.
- `for`, `create`, `edit`, `react`, and `report` now revalidate the parent content target.
- Comment-ID mutations resolve the owning target before validation/mutation and return uniform 404 when the target is unavailable to the viewer.
- Added behavioral audience coverage for public/member/VIP/role decisions.

## Batch 6 — safe local redirects (NN-HTTP-01)

- `Response::redirect()` now accepts only local absolute paths beginning with exactly one `/`.
- Scheme-relative destinations (`//host`), backslashes, ASCII control/space characters (`0x00-0x20`) and DEL (`0x7F`) are rejected at the central HTTP boundary.
- Normal local paths, query strings and fragments remain supported. External HTTP/HTTPS destinations continue to use `Response::externalRedirect()`.
- Comments `return_to` fallback validation was aligned with the central redirect contract so invalid user input falls back to `/` instead of surfacing an exception.
- Bundled redirect call sites were reviewed; dynamic redirects either construct local application paths or pass through existing allowlist/fallback validation.
- No database migration or routing change is introduced by Batch 6.

### Batch 6 acceptance

```bash
php vendor/bin/phpunit --filter ResponseTest tests/Unit/ResponseTest.php
composer test:checkpoint
```
