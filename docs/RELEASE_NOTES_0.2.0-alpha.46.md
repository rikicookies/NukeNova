# NovaNuke 0.2.0-alpha.46

Alpha.46 begins the Admin improvement phase by putting actionable work and common content-creation shortcuts directly on the dashboard.

## Upgrade from Alpha.45

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.45
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.45
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. Open the Admin dashboard with a Super Administrator.
2. Confirm module issues and pending comments appear before unpublished content under Needs attention.
3. Confirm zero-count attention items are omitted.
4. Confirm Create actions only appear for enabled modules the account can manage.
5. Open each attention item and quick action and confirm it reaches the existing protected Admin screen.
6. Repeat with a limited role and confirm unauthorized metrics and shortcuts are absent.
