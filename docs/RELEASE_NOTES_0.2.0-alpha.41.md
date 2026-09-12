# NovaNuke 0.2.0-alpha.41

Alpha.41 begins the UX-consistency milestone with a shared public breadcrumb trail for News, Pages, Downloads and Web Links.

## Upgrade from Alpha.40

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.40
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.40
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

Check `/news`, `/pages`, `/downloads` and `/links`, then open one detail item in each module. Every trail must begin at Home, return to its module list and mark the current page without linking it. For a child Page, confirm the visible parent appears between Pages and the current title. Repeat with Default, Classic and NovaModern.
