# NovaNuke 0.4.0-beta.24

## RC preparation — mail, themes, authorization and regression batch

Beta 24 groups several Release Candidate hardening items into one checkpoint.

### Theme package validation

```bash
php bin/cms theme:check
```

The command validates bundled theme manifests, NovaNuke compatibility, declared layouts, screenshots and asset directories. The isolated MySQL suite now also installs the bundled themes, switches between them, protects the active theme from uninstall and verifies an inactive theme can be removed cleanly.

### Mail configuration preflight

```bash
php bin/cms mail:check
```

This validates the selected transport and SMTP configuration structure without opening an SMTP delivery connection. Log mail remains valid for development and reports SMTP as an informational warning. A configured SMTP transport must pass strict host/port/encryption/sender/credential validation.

The check never includes the configured SMTP password in diagnostics.

### Authorization hardening

`security:audit` now verifies that the Super Administrator role still owns every required Core permission. Integration coverage proves removal of a required permission is detected.

### Checkpoint integration

- `check:rc-source` now includes theme validation.
- `check:site` includes mail configuration validation.
- `check:release` includes both theme and mail validation.

No database migration or visual redesign is introduced by Beta 24.
