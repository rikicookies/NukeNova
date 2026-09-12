# NovaNuke 0.2.0-alpha.50

Alpha.50 continues the Admin Experience milestone by bringing the core Users and Roles lists into the same navigation and action pattern already used by the primary content administration screens.

## What changed

- Users and Roles now use the shared Admin breadcrumb and page-header components.
- Per-row Manage/View actions use the shared accessible Admin row-action component.
- Empty tables use the shared Admin empty-state component.
- The Users list retains the existing VIP status filters.
- Create account appears in the Users header only when the current administrator already has `users.manage`, `users.assign_roles` and Super Administrator access. The protected create route keeps the same server-side authorization checks.

## Upgrade from Alpha.49

Back up and verify the current site before replacing files. After applying this patch, run:

```bash
php bin/cms upgrade:check --from=0.2.0-alpha.49
php bin/cms migrate:status
php bin/cms cache:clear
composer test
composer test:integration
php bin/cms release:check
php bin/cms upgrade:complete --from=0.2.0-alpha.49
```

No database migration, module/theme update or Composer dependency is included.

## Browser checks

1. Open Admin → Users as a Super Administrator and confirm the breadcrumb, shared header, VIP filters and Create account action appear.
2. Confirm each user row exposes its Manage action and that a VIP filter with no matches renders the shared empty state.
3. Sign in as an administrator with `users.view` but without the full account-creation requirements and confirm the Users list remains accessible while Create account is absent.
4. Open Admin → Roles and confirm the breadcrumb, shared header and per-role View/View-edit actions render correctly.
5. Confirm no Users or Roles list contains the obsolete Return to dashboard link.
