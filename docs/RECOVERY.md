# Recovery

## Site returns an internal error

1. Leave `APP_DEBUG=false` on a public server.
2. Copy the reference shown on the error page.
3. Find that reference in `storage/logs/novanuke.log`.
4. Check PHP version/extensions, database availability, writable storage and recent file changes.
5. Do not paste `.env`, SMTP conversations, reset links or full production logs into a public ticket.

NovaNuke redacts common credential, authorization and token patterns from exception logs, but logs must still be treated as private.

## Interrupted database migration

NovaNuke records every Core and module schema operation in `migration_operations` before executing DDL. A `running` row means the process ended without reporting a result; a `dirty` row means NovaNuke caught an error after the attempt began. `completed` rows are audit evidence and require no operator action. The existing `migrations` and `module_migrations` tables remain the authoritative history.

Do not delete or edit any of these rows. Keep maintenance mode enabled and inspect the durable state:

```bash
php bin/cms migrate:status
```

After correcting the reported cause, reconcile an interrupted Core migration:

```bash
php bin/cms migrate:recover
```

For a module install, update, or data-deleting uninstall, use its slug:

```bash
php bin/cms migrate:recover --module=welcome
```

Recovery verifies the migration file's SHA-256 against the interrupted attempt. A changed file is rejected. Bundled migrations inspect their schema/data postconditions: if all effects are already present, NovaNuke records the existing history without repeating the DDL; otherwise the idempotent migration resumes and verifies its final state. Module rollback recovery executes in reverse history order.

If recovery says an interrupted migration is legacy/non-recoverable, do not retry it or edit the ledger. Restore the matched pre-update backup, or deploy a reviewed recoverable version of that exact migration. A database account must support MySQL/MariaDB `GET_LOCK`; failure to acquire the advisory lock is a hard stop protecting against concurrent schema runners.

## Locked out during maintenance

Login, password recovery and administrative routes remain available. Sign in through `/login`, open `/admin/settings` and disable maintenance. Do not delete the installation lock.

## Lost administrator access

Use normal password recovery after confirming SMTP delivery. If email is unavailable, restore access through a controlled database recovery performed by the server owner; never add a public bypass route or weaken core authorization.

## Damaged update

1. Keep maintenance enabled.
2. Restore the exact prior application files.
3. Restore the matching database backup when migrations changed schema/data.
4. Preserve private downloads and `.env`.
5. Run `composer install`, `php bin/cms cache:clear` and `php bin/cms release:check`.

## Installer unexpectedly appears

Stop and restore `storage/installed.lock` from a trusted backup. Verify `.env` and the database are intact. Do not submit the installer against an existing production database.


## Verified private-file restore

Never extract a NovaNuke backup directly over a live application tree.

First verify the latest matched backup pair and prove the file archive can actually be extracted:

```bash
php bin/cms backup:verify
php bin/cms backup:restore-check
```

`backup:restore-check` uses a disposable temporary directory and removes it after verification. It does not overwrite the live site.

Restore the file archive into a new empty disposable directory:

```bash
php bin/cms backup:restore-files --archive=/protected/path/novanuke-files-....tar --destination=/new/empty/restore
```

The restore command verifies the TAR manifest/checksums before extraction, rejects a non-empty destination and has no overwrite/force mode.

Database SQL restore is deliberately not automated by NovaNuke. Create/select an empty disposable database with the database server's normal administration tooling, import the verified SQL backup there, then pair it with the matching application release and restored private files. This keeps database selection and destructive replacement outside an application CLI shortcut.

After recovery, run `php bin/cms migrate:status` and installed-site checks using the application release matching the backup before exposing the restored site.
