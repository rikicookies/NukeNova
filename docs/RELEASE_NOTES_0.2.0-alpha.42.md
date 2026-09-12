# NovaNuke 0.2.0-alpha.42

Alpha.42 gives the primary public directories consistent empty states and pagination through two shared Twig components.

## Upgrade from Alpha.41

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.41
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.41
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. Open empty News, Pages, Downloads and Web Links directories and confirm each message is readable in Default, Classic and NovaModern.
2. Populate enough News, Downloads and Web Links records to produce multiple pages.
3. Confirm the active page is visually marked and exposes `aria-current="page"`.
4. Search or reorder Downloads and Web Links, change pages and confirm those filters remain active.
