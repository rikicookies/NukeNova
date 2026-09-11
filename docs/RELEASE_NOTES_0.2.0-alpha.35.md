# NovaNuke 0.2.0-alpha.35

Alpha.35 hardens clean installation and makes pre-installation requirements auditable from the command line without accepting secrets there.

## Changes

- Added `php bin/cms install:check` before application boot.
- Restricted the site URL to safe HTTP/HTTPS base URLs.
- Restricted database host values so they cannot append PDO DSN options.
- Refused automatic replacement of an existing `.env` file or symlink.
- Refused installation into any database that already contains tables.
- Expanded focused installer tests and installation documentation.

## Upgrade from alpha.34

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Overlay the Alpha.35 update files.
3. Run `composer install`, `composer test` and `php bin/cms release:check`.

No migration, module update, theme update or Composer dependency change is required for an existing installation. The new empty-database and `.env` rules apply only while NovaNuke is not installed.

## Focused tests

```bat
vendor\bin\phpunit tests\Unit\InstallationValidatorTest.php tests\Unit\EnvWriterTest.php tests\Unit\RequirementsCheckerTest.php tests\Unit\InstallerSafetyContractTest.php tests\Unit\InstallationLockTest.php tests\Unit\ReleaseVersionTest.php
composer test:integration
```

## Clean-install acceptance

1. Use a new application directory without `.env` or `storage/installed.lock`.
2. Run `composer install` and `php bin/cms install:check`.
3. Confirm a populated database is rejected without changing its tables.
4. Complete installation using a separate empty database.
5. Confirm `.env` and the versioned installation lock are created and `/install` becomes inaccessible.
6. Run the Alpha.34 module and Demo Content acceptance checks.

## Deliberate limitation

Alpha.35 does not add a non-interactive CLI installer. Passing database and administrator passwords as ordinary command arguments would expose them through shell history or the process list. A future CLI installer requires a portable hidden-input or protected configuration-file design.
