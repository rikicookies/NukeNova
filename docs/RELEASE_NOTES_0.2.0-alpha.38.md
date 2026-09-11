# NovaNuke 0.2.0-alpha.38

Alpha.38 verifies the latest backup pair before NovaNuke files or data are changed.

## Verification

On Alpha.38 and later, create both backups and verify them before replacing application files:

```bat
php bin/cms backup:database
php bin/cms backup:files
php bin/cms backup:verify
```

The database, files and matched-pair entries must report `PASS`. NovaNuke requires the two files to have been created no more than ten minutes apart. Keep the displayed filenames and SHA-256 fingerprints with the off-server copy.

The command reads only the newest `novanuke-db-*.sql` and `novanuke-files-*.tar` files from `storage/private/backups`. It does not execute SQL, extract files or accept arbitrary paths.

## Upgrade from Alpha.37

Alpha.37 does not yet contain `backup:verify`. While Alpha.37 is still intact, enable maintenance mode and create both backups:

```bat
php bin/cms backup:database
php bin/cms backup:files
```

Preserve persistent files and overlay Alpha.38. Before Composer, migrations or module updates can change state, verify the backups and continue only if all three checks pass:

```bat
php bin/cms backup:verify
php bin/cms upgrade:check --from=0.2.0-alpha.37
php bin/cms migrate:status
php bin/cms migrate
php bin/cms cache:clear
```

Alpha.38 adds no database migration, module/theme update or Composer dependency.

## Focused tests

```bat
vendor\bin\phpunit tests\Unit\BackupVerifierTest.php tests\Unit\FileBackupTest.php tests\Unit\UpgradeCliContractTest.php tests\Unit\ReleaseVersionTest.php
composer test:integration
php bin/cms release:check
```

An integrity check is not a substitute for a periodic restoration test on a disposable database and copy of the site.
