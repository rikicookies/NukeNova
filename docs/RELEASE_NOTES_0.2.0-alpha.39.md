# NovaNuke 0.2.0-alpha.39

Alpha.39 integrates backup integrity verification directly into the upgrade preflight.

## Upgrade from Alpha.38

While Alpha.38 is still intact, enable maintenance mode and create and verify a fresh backup pair:

```bat
php bin/cms backup:database
php bin/cms backup:files
php bin/cms backup:verify
```

Preserve persistent files, overlay Alpha.39 and run:

```bat
php bin/cms upgrade:check --from=0.2.0-alpha.38
php bin/cms migrate:status
php bin/cms migrate
php bin/cms cache:clear
```

`upgrade:check` now fails unless the newest database and file backups pass their structural and manifest checks, form a matched pair and are no more than 24 hours old. The command remains read-only.

Alpha.39 adds no database migration, module/theme update or Composer dependency.

## Focused tests

```bat
vendor\bin\phpunit tests\Unit\UpgradeReadinessTest.php tests\Unit\BackupVerifierTest.php tests\Unit\UpgradeCliContractTest.php tests\Unit\ReleaseVersionTest.php
composer test:integration
php bin/cms release:check
```
