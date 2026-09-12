# NovaNuke 0.3.0-beta.5

## Production hardening — installer recovery, secrets and backup boundaries

- Failed fresh installations can be retried cleanly: after the installer confirms an empty database, failures during migrations, account/settings creation, `.env` activation or lock creation trigger cleanup of the NovaNuke-owned schema and incomplete `.env`.
- The installer never drops the selected database itself and never performs cleanup before the empty-database ownership check passes.
- `.env` is atomically written and restricted to owner-only permissions on POSIX hosts.
- `production:check` validates `.env` as a regular non-symlink file and checks its POSIX permission bits.
- `storage/private/.htaccess` denies Apache access as defense in depth when a shared-hosting document root is accidentally too broad.
- Backup writers reject symlinked backup directories; the verifier rejects symlinked/non-regular backups and permissive POSIX backup files.

No database migration is required.
