# NovaNuke 0.2.0-alpha.45

Alpha.45 completes the first UX-consistency pass with shared empty states and record actions in the primary Admin content tables.

## Upgrade from Alpha.44

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.44
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.44
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. Visit the News, Pages, Downloads and Web Links Admin lists with and without records.
2. Confirm empty tables provide a useful message and Create action where appropriate.
3. Confirm every record has Edit and published records additionally have View.
4. Confirm drafts have no public View action.
5. Delete a disposable Web Link and verify its POST confirmation and CSRF behavior remain intact.
