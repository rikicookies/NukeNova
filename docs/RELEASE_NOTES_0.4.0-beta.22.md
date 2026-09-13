# NovaNuke 0.4.0-beta.22

## RC preparation — automated fresh-installer acceptance

Beta 22 adds a true installer integration regression to the isolated MySQL suite.

The test creates a random empty database and temporary application root, invokes the real `InstallerService`, and verifies:

- all current Core migrations execute;
- `.env` is generated without embedding the Administrator password;
- `storage/installed.lock` records the current NovaNuke version;
- `system.core_version` matches the running release;
- the Super Administrator and role assignment exist;
- required runtime storage directories are provisioned.

A second installer integration test starts with a non-empty database and proves NovaNuke refuses installation without deleting the pre-existing table or creating `.env` / installation lock state.

This does not replace the manual browser fresh-install RC pass, but it moves a large part of installer acceptance into the repeatable checkpoint suite.

No runtime feature or database migration is introduced by Beta 22.

### Installed-site validation

After applying the Beta 22 update to an existing test site, complete the supported upgrade from its recorded version, then run:

```bash
composer test:checkpoint
composer check:site
```
