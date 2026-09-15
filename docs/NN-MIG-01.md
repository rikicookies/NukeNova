# NN-MIG-01 — Recoverable MySQL migrations

## Original failure mode

MySQL and MariaDB may commit DDL implicitly. If a migration completed one `CREATE TABLE`, `ALTER TABLE`, or `CREATE INDEX` and then failed, a transaction rollback could not reliably restore the original schema. Re-running the whole migration without inspecting the database could repeat an incompatible operation or run later migrations against a partial schema.

## Durable protocol

`migration_operations` is an additive control ledger. The existing `migrations` and `module_migrations` tables remain authoritative history and legacy completed rows remain completed without backfill or re-execution.

```text
pending -> running -> completed
              |
              v
            dirty
              |
              v
           recovery -> completed
```

- `pending`: a migration file has no classic history row and no active operation.
- `running`: the durable marker was written before migration code started; an abrupt process death may leave it here.
- `dirty`: NovaNuke caught an error after writing the running marker.
- `completed`: the migration postcondition and classic history update were committed together.

Every operation records Core/module scope, migration identifier, direction, source SHA-256, attempt count, timestamps, and a bounded error summary. Normal Core migration, module install/update, and module uninstall paths refuse to start while any running/dirty operation exists.

## Locking

Core and module runners share one database-scoped MySQL/MariaDB advisory lock based on the selected schema name. `GET_LOCK` works without Redis or a daemon and the server releases it when the owning database connection closes. Acquisition timeout or conflict is a hard stop.

## Recovery

Use `php bin/cms migrate:status` first. Then use `php bin/cms migrate:recover` for Core or `php bin/cms migrate:recover --module=SLUG` for a module. Recovery requires the unchanged migration checksum and a `RecoverableMigration` implementation. It verifies final schema/data postconditions, resumes only idempotent missing work, and does not run unrelated pending migrations.

If the file changed, is missing, lacks recovery postconditions, or cannot prove the expected final state, recovery stops without marking the migration completed. Restore the matched pre-update backup or deploy a reviewed recoverable form of that exact migration.

## Existing migration audit

All 52 bundled migration files implement `RecoverableMigration`.

- Table-creation migrations use `CREATE TABLE IF NOT EXISTS` and verify their required table set. Multi-table Core/module migrations are the highest partial-DDL risk but are naturally resumable and explicitly reconciliable.
- Column/index migrations use `MigrationSchema` existence checks or explicit postconditions, so missing steps can resume individually.
- Seed/settings/permission migrations use duplicate-safe writes and verify stable marker values where required.
- Complex membership and Wiki schema changes use explicit `isApplied()` / `isRolledBack()` methods where the declarative trait is insufficient.
- No bundled migration was classified as impossible to reconcile automatically. Third-party legacy migrations remain fail-closed after interruption.

## Operational procedure

Before recovery, enable maintenance mode and create a matched database/files backup. Do not edit `migration_operations`, `migrations`, or `module_migrations` manually. After recovery, run status again and continue only when recovery and missing-file totals are zero.
