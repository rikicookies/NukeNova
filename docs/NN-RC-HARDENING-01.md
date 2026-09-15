# NN-RC-HARDENING-01

This checkpoint closes a distribution-safety gap without changing runtime CMS behavior or the release version. `php bin/cms rc:check` now rejects session data, public uploads, private user files, `.env` variants and root-level database/archive artifacts in addition to the existing log, cache, backup and installation-lock checks.

Run the gate against the exact clean source tree before creating a release archive:

```bash
composer check:rc-source
composer test:checkpoint
```

The release archive must still exclude `vendor`; install dependencies after extraction with the locked Composer dependencies.
