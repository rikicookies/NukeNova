# NovaNuke 0.4.0-beta.18

## Release-candidate readiness — lifecycle-aware validation

Beta 18 fixes the validation-model problem exposed by testing Beta 17 on an already-installed Laragon site.

`install:check` intentionally expects no `.env` and no `storage/installed.lock`, so it is not a valid health check after installation. NovaNuke now separates lifecycle states explicitly.

### Before installation

```bash
composer check:install
```

This uses the installer requirements and expects the project to be uninstalled.

### Existing development/staging installation

```bash
composer check:site
```

This checks:

- `.env` exists as a regular file
- `storage/installed.lock` exists and is structurally valid
- recorded Core version matches the running release
- no pending/missing Core or installed-module migrations
- no installed module update remains
- required runtime directories exist and are writable
- Membership integrity
- optional Payment receipt integrity

### Production release validation

```bash
composer check:release
```

This additionally runs distribution checklist/smoke and production-readiness checks such as HTTPS, production environment, debug disabled, secure sessions, security headers and deployment hardening.

A local Laragon installation can legitimately pass `check:site` while failing `check:release` because it is not configured as a public production deployment.

No database migration is introduced by Beta 18.
