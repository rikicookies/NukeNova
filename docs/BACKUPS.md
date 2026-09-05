# Backups and restoration

## Create a database backup

From the project root:

```bash
php bin/cms backup:database
```

NovaNuke creates an atomic SQL dump under `storage/private/backups/` with restrictive file permissions. The command does not accept a browser-supplied path, and incomplete `.part` files are removed after failure.

The SQL file contains sensitive data including password hashes, reset tokens, private messages and site content. Download it through SSH/SFTP or the host file manager, encrypt it, store it off-server and delete old server copies according to a retention policy. Never place it under `public/` or commit it.

Database backup does not include:

- `.env` (save it separately through a secure channel);
- local download files in `storage/private/downloads/`;
- custom modules, themes or uploaded public media.

Back up those directories separately without making them web-accessible.

## Restore test

Restoration is deliberately not exposed through the web panel. Create an empty database, import the SQL file with MySQL/MariaDB tools or phpMyAdmin, restore the matching application files and private assets, then configure `.env`. Test restoration periodically on a non-production system. A backup that has never been restored is not yet proven usable.

The built-in exporter is intentionally portable for shared hosting. Large sites may prefer the provider's snapshot system or `mysqldump` because those tools scale better and can coordinate database locking options.

