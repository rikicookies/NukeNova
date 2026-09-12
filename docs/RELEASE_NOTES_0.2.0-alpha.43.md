# NovaNuke 0.2.0-alpha.43

Alpha.43 standardizes public directory headers and adds discreet, permission-aware management shortcuts to News, Pages, Downloads and Web Links.

## Upgrade from Alpha.42

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.42
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.42
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. As a guest, open `/news`, `/pages`, `/downloads` and `/links`; no Manage action should appear.
2. Sign in with the matching content permission and confirm Manage opens that module's existing Admin list.
3. Confirm News still exposes RSS and Web Links still exposes Submit a link.
4. Repeat in Default, Classic and NovaModern at desktop and mobile widths.
