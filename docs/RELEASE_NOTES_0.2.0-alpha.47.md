# NovaNuke 0.2.0-alpha.47

Alpha.47 adds a safe operational overview to the Admin dashboard and makes the new dashboard utility cards visually consistent with NovaModern.

## Upgrade from Alpha.46

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.46
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.46
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. Open the Admin dashboard as a Super Administrator and locate Site health.
2. Confirm maintenance mode, database state, writable storage and production configuration have clear statuses.
3. Follow each card to the existing Settings or System Information screen.
4. Enable maintenance mode temporarily and confirm its dashboard status changes to Enabled.
5. Sign in with an Admin role lacking `settings.manage` and confirm both health details and configuration warnings are absent.
6. With NovaModern active, confirm health, attention and quick-action cards use readable light backgrounds and text.
