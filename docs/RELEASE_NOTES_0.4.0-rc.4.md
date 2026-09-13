# NovaNuke 0.4.0-rc.4 — Release Reliability & Recovery

RC.4 is a reliability-only release candidate. It is being completed in six audited batches; it is not ready for promotion until every authorized finding has a tested disposition.

## Batch 1 — recoverable migrations (NN-MIG-01)

- Core and module migrations share a database-scoped MySQL/MariaDB advisory lock, preventing concurrent schema runners without shell access.
- The additive `migration_operations` control table records `running`, `dirty`, and `completed`, the direction, attempt count, error summary, timestamps, and migration-file SHA-256.
- Existing `migrations` and `module_migrations` rows remain authoritative and are neither renamed nor backfilled.
- Every bundled migration now has verifiable applied/rolled-back postconditions and repeat-safe schema/data steps.
- `php bin/cms migrate:recover` reconciles Core; `--module=SLUG` covers module install/update and data-deleting uninstall recovery.
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
