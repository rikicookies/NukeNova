# NovaNuke 0.2.0-alpha.44

Alpha.44 standardizes navigation and headers in the News, Pages, Downloads and Web Links administration lists.

## Upgrade from Alpha.43

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.43
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.43
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

Visit `/admin/news`, `/admin/pages`, `/admin/downloads` and `/admin/web-links`. Each list must show Admin/current breadcrumbs, its description, a Create action and a View-site action. Confirm those routes work in NovaModern and in the public themes if they are also used for Admin rendering.
