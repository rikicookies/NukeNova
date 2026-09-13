# NovaNuke 0.4.0-beta.19

## Release Candidate preparation — source/package acceptance

Beta 19 adds the source-package gate and formal acceptance matrix needed before the first Release Candidate.

### Clean source package

Run before installation:

```bash
composer check:rc-source
```

The source gate rejects local `.env`, `storage/installed.lock`, runtime log/cache/backup artifacts, stale release metadata and an incomplete RC documentation set. It also executes the normal release checklist and distribution smoke check.

### Acceptance matrix

`docs/RC_ACCEPTANCE.md` defines the required evidence beyond PHPUnit:

- clean package
- fresh installation
- real existing-site upgrade
- generated backup verification and actual restore
- authorization/security smoke checks
- production-like SMTP workflows
- production configuration
- bundled module/theme functional regression
- recorded environment/package hashes and accepted warnings

Visual polish of working legacy module screens remains outside RC scope and stays deferred until the agreed roadmap is complete.

### Validation

For the clean package:

```bash
composer test:checkpoint
composer check:rc-source
```

For an installed development/staging copy after upgrade:

```bash
composer check:site
```

For a production-like deployment:

```bash
composer check:release
```

No database migration is introduced by Beta 19.
