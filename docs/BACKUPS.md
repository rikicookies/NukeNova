# Backups and restoration

## Create a database backup

From the project root:

```bash
php bin/cms backup:database
```

NovaNuke creates an atomic SQL dump under `storage/private/backups/` with owner-only file permissions. The backup directory itself must be a real directory and not a symbolic link. The command does not accept a browser-supplied path, and incomplete `.part` files are removed after failure.

The SQL file contains sensitive data including password hashes, reset tokens, private messages and site content. Download it through SSH/SFTP or the host file manager, encrypt it, store it off-server and delete old server copies according to a retention policy. Never place it under `public/` or commit it.

Database backup does not include:

- `.env` (save it separately through a secure channel);
- local download files, avatars, custom modules, themes or uploaded public media.

Create a matching file backup with:

```bash
php bin/cms backup:files
```

This creates a private TAR archive containing `modules/`, `themes/`, `public/uploads/`, `storage/private/avatars/` and `storage/private/downloads/`. It never follows symbolic links and does not include `.env`, database data, logs, sessions, caches or other backups. `NOVANUKE-BACKUP.json` inside the archive records each path, byte size and SHA-256 digest. The command also prints the archive SHA-256 so it can be checked after moving the file off-server.

The archive can contain executable module code and private user files. Treat it as sensitive, keep it outside `public/`, encrypt off-server copies and restore only code from a trusted backup. Save `.env` separately through a secure channel.

## Verify the backup pair

Immediately after creating both files, run:

```bash
php bin/cms backup:verify
```

The database, files and matched-pair checks must all report `PASS`. On POSIX hosts verification also rejects backup files that are accessible to group/other users. NovaNuke verifies the latest SQL envelope and fingerprint, every TAR header and manifest size/hash, safe archive paths and a complete terminator without executing or extracting anything. The two files must have been created no more than ten minutes apart. Record their filenames and displayed SHA-256 fingerprints with the off-server copy.

`php bin/cms upgrade:check --from=VERSION` repeats these integrity checks and additionally requires backups created within the last 24 hours. Neither command proves authenticity against an attacker who can replace both a backup and its recorded fingerprint.

## Restore test

Restoration is deliberately not exposed through the web panel. Create an empty database, import the SQL file with MySQL/MariaDB tools or phpMyAdmin, extract the matching file archive over a clean copy of the same NovaNuke release, then configure `.env`. Compare restored files with the manifest before exposing the site. Test restoration periodically on a non-production system. A backup that has never been restored is not yet proven usable.

The built-in exporter is intentionally portable for shared hosting. Large sites may prefer the provider's snapshot system or `mysqldump` because those tools scale better and can coordinate database locking options.
