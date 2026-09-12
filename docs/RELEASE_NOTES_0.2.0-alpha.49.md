# NovaNuke 0.2.0-alpha.49

Alpha.49 makes manual account and VIP administration easier to reach from the dashboard without weakening the existing authorization rules.

## Upgrade from Alpha.48

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.48
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.48
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. Open the Admin dashboard as a Super Administrator and confirm Create user account appears under Quick actions.
2. Follow the shortcut and create a disposable Member account using the existing form.
3. Give a test account VIP access that expires within seven days and confirm the dashboard warning appears.
4. Follow that warning and confirm it opens the Active VIP user filter.
5. Revoke or extend the entitlement beyond seven days and confirm the warning count updates or disappears.
6. Sign in as a non-Super Administrator and confirm the shortcut and VIP-expiration warning are absent.
