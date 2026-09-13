# NovaNuke 0.4.0-beta.20

## RC test isolation correction

Beta 20 fixes the testing-model issue exposed when the Beta 19 update patch was applied to an existing installed site.

`composer test:checkpoint` must be safe to run on an active development/staging installation containing legitimate `.env`, `storage/installed.lock`, Twig cache, logs and protected backups. The RC cleanliness unit test now creates an isolated temporary distribution fixture and validates cleanliness there.

The actual source-package gate remains unchanged:

```bash
composer check:rc-source
```

Run that command only against a freshly extracted clean Full Source package. It should continue to reject `.env`, installation locks, runtime cache/logs/backups and other local artifacts.

For an installed site use:

```bash
composer test:checkpoint
composer check:site
```

No database migration or runtime behavior change is introduced by Beta 20.
