# NN-RC4-FINAL — Promoted release

This tree consolidates the validated RC.4 work and is promoted with runtime version `0.4.0-rc.4` after explicit approval.

## Included work

- NN-MIG-01: recoverable core and module migrations.
- NN-BACKUP-01: coordinated, verifiable backup sets and staged restore checks.
- NN-MAIL-01: production SMTP readiness and explicit delivery acceptance.
- NN-THEME-01: validated staging and atomic theme asset publication.
- NN-SEC-01: comment target audience revalidation.
- NN-HTTP-01: safe local redirect enforcement.
- NN-INSTALL-UX-01: recommended fresh-install modules, NovaModern, modules menu, and public logout.
- NN-MEM-01: transactional, idempotent membership lifecycle notifications.
- UX-AJAX-01/02/03: progressive admin actions with POST/CSRF fallback retained.
- NN-MOD-01 v2: fail-closed module registration and boot isolation without changing Module API 1.0.

## Final acceptance gate

Run against this exact full-source artifact:

```bash
composer install
composer test:checkpoint
composer test:integration
composer check:site
php bin/cms rc:check
php bin/cms release:check
php bin/cms release:smoke
php bin/cms theme:check
php bin/cms migrate:status
```

Production-only checks such as real SMTP delivery acceptance and disposable backup restore remain operator acceptance items on Bluehost. A local structural PASS must not be described as proof of those external workflows.

## Release rule

Do not change `Version::CURRENT` or release filenames after packaging. Declare the exact artifact GOLDEN only after its hashes are recorded and the applicable gates pass. Fresh installs persist `0.4.0-rc.4`; upgrades from `0.4.0-rc.3` remain explicitly supported and update `system.core_version` only through the existing upgrade-completion lifecycle.
