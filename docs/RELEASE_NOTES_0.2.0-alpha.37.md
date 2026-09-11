# NovaNuke 0.2.0-alpha.37

Alpha.37 makes migration failure behavior explicit and conservative. It adds no migration of its own.

## What changed

- Core and module updates stop before running anything when an executed migration file is absent.
- The first failed migration ends the batch and is not recorded as completed.
- The CLI names the failed migration and directs the operator to restore the matched pre-upgrade database and files.
- NovaNuke does not attempt automatic DDL rollback because MySQL/MariaDB may commit schema changes implicitly.

## Upgrade from Alpha.36

Before replacing files, enable maintenance mode and create both backups:

```bat
php bin/cms backup:database
php bin/cms backup:files
```

Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`, overlay Alpha.37, then run:

```bat
php bin/cms upgrade:check --from=0.2.0-alpha.36
php bin/cms migrate:status
php bin/cms migrate
php bin/cms cache:clear
```

There is no database, bundled module/theme or Composer dependency update in Alpha.37.

## If a migration fails

Leave maintenance mode enabled. Do not retry blindly and do not edit migration-history tables. Restore the database and files from the same pre-upgrade checkpoint, verify `migrate:status`, correct the cause in a disposable copy and restart the update with fresh backups.

## Focused tests

```bat
vendor\bin\phpunit tests\Unit\MigrationSafetyContractTest.php tests\Unit\UpgradeCliContractTest.php tests\Unit\ReleaseVersionTest.php
composer test:integration
php bin/cms release:check
```
