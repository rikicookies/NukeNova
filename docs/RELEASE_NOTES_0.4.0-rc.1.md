# NovaNuke 0.4.0-rc.1

## First Release Candidate

RC.1 begins the feature-freeze stage for NovaNuke 0.4.0.

The package includes the full Beta 28 visual consistency pass and the pre-RC compatibility corrections discovered while upgrading an older populated NovaNuke installation.

### Key RC.1 acceptance scenarios

1. Fresh install from a newly extracted archive into a database that already exists but contains no tables.
2. Existing-site upgrade from Beta 28 using the supported upgrade workflow.
3. Backup creation, verification and disposable file restore.
4. Production-like installation on Bluehost with HTTPS, Linux filesystem permissions, real SMTP and a pre-created MySQL database.
5. `composer check:release` and `php bin/cms rc:deployment` on the Bluehost target.

RC.1 introduces no database migration and no new feature scope.
