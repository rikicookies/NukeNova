# NovaNuke 0.2.0-alpha.40

Alpha.40 closes the installation/upgrade-discipline milestone by recording the installed Core version after a verified upgrade completes.

## Upgrade from Alpha.39

While Alpha.39 is intact, enable maintenance mode and create and verify a fresh backup pair:

```bat
php bin/cms backup:database
php bin/cms backup:files
php bin/cms backup:verify
```

Preserve persistent files, overlay the Alpha.40 patch and run:

```bat
php bin/cms upgrade:check --from=0.2.0-alpha.39
php bin/cms migrate:status
php bin/cms migrate
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.39
```

The first completion on an installation created before Alpha.40 reports one non-blocking legacy warning, then stores Alpha.40 as the installed Core version. Future preflights and completions reject a source version that differs from this record.

Alpha.40 adds no database migration, bundled module/theme update or Composer dependency. Keep maintenance mode enabled until completion succeeds and browser smoke tests pass.

## Focused tests

```bat
vendor\bin\phpunit tests\Unit\UpgradeCompletionTest.php tests\Unit\UpgradeReadinessTest.php tests\Unit\UpgradeCliContractTest.php tests\Unit\ReleaseVersionTest.php
composer test:integration
php bin/cms release:check
```
