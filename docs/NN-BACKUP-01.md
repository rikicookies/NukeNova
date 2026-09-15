# NN-BACKUP-01

NN-BACKUP-01 makes `backup:create` a three-component operation: SQL, TAR and an external `manifest.json`. Work happens in a private `.incomplete-set-...` staging directory. The completed directory is published only after both artifacts and their metadata verify.

NovaNuke continues to export SQL through PDO, so Laragon and shared hosting do not require `mysqldump`, `exec` or `proc_open`. InnoDB tables use a repeatable-read consistent snapshot. Non-transactional tables are recorded as a warning because they cannot share that guarantee. The database snapshot and filesystem walk are consecutive, not a perfectly atomic cross-resource snapshot.

## Laragon

From the NovaNuke directory:

```bat
php bin\cms migrate:status
php bin\cms backup:create
php bin\cms backup:verify
php bin\cms backup:restore-check
```

Copy the complete `storage\private\backups\set-...` directory outside the web root. For the disposable SQL restore test, create an empty database and configure `NOVANUKE_BACKUP_VERIFY_DSN`, `NOVANUKE_BACKUP_VERIFY_USERNAME`, and `NOVANUKE_BACKUP_VERIFY_PASSWORD`.

## Bluehost/shared hosting

Run the same commands through SSH from the application directory. If SSH is unavailable, use the host terminal feature. Keep sets outside `public/`, download the complete directory through SFTP/File Manager, and remove server copies according to your retention policy. PHP must be able to write `storage/private/backups`; do not use `chmod 777`.

The file archive excludes `.env`, `vendor`, `.git`, cache, logs, sessions and the backup directory itself. Preserve `.env` separately through a secure channel. NovaNuke intentionally does not expose restore through HTTP.
