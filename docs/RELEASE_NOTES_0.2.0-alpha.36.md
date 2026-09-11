# NovaNuke 0.2.0-alpha.36

Alpha.36 introduces an explicit, read-only upgrade preflight for selected recent releases.

## Supported direct sources

- `0.2.0-alpha.33`
- `0.2.0-alpha.34`
- `0.2.0-alpha.35`

Older installations must follow their documented intermediate release path or use a clean installation and controlled data migration.

## Upgrade from Alpha.35

Before replacing files, enable maintenance mode and create both backups:

```bat
php bin/cms backup:database
php bin/cms backup:files
```

Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`, overlay Alpha.36, then run:

```bat
php bin/cms upgrade:check --from=0.2.0-alpha.35
php bin/cms migrate:status
php bin/cms migrate
php bin/cms cache:clear
```

The preflight fails for a missing/linked `.env` or installation lock, stale/missing backups, unsupported source, downgrade or missing migration source files. Pending migrations and module updates are warnings because they are the work the operator is preparing to apply.

No migration, module/theme update or Composer dependency is introduced by Alpha.36 itself.

## Focused tests

```bat
vendor\bin\phpunit tests\Unit\UpgradeReadinessTest.php tests\Unit\UpgradeCliContractTest.php tests\Unit\ReleaseVersionTest.php
composer test:integration
php bin/cms release:check
```

## Acceptance matrix

For each supported source, use a disposable copy and database:

1. Create database and file backups while still running the source release.
2. Enable maintenance mode and overlay Alpha.36 while preserving persistent files.
3. Run `upgrade:check` with the exact source version.
4. Apply core migrations and module updates reported by the system.
5. Confirm `migrate:status` returns no pending/missing migration or module update.
6. Test login, Admin, active theme, representative content, private files and Demo Content records when installed.
7. Disable maintenance mode only after the smoke test passes.

Record the PHP and database versions plus pass/fail for Alpha.33 → Alpha.36, Alpha.34 → Alpha.36 and Alpha.35 → Alpha.36. Automated preflight does not replace those restoration-capable acceptance tests.
