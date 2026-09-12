# NovaNuke 0.2.0-alpha.48

Alpha.48 makes the dashboard priority queue aware of existing abuse and broken-resource reports across installed modules.

## Upgrade from Alpha.47

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.47
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.47
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. Create one disposable open report in Comments, Downloads, Web Links and Private Messages.
2. Open the Admin dashboard and confirm each available report queue appears under Needs attention.
3. Confirm Reported Comments and Private-message abuse reports are ordered before broken-resource reports.
4. Follow each item and confirm it opens the existing protected module administration screen.
5. Resolve each report and confirm its zero-count dashboard item disappears.
6. Test a limited administrator and confirm report items are absent without the corresponding module permission.
