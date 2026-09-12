# NovaNuke 0.2.0-alpha.52

Alpha.52 continues the Admin Experience pass by bringing the primary content Create/Edit screens into the same shared navigation/header pattern already used by their Admin lists.

## Changed

- News Create/Edit now uses Admin > News > Create/Edit breadcrumbs and the shared page header.
- Pages Create/Edit now uses Admin > Pages > Create/Edit breadcrumbs and the shared page header.
- Downloads Create/Edit now uses Admin > Downloads > Create/Edit breadcrumbs and the shared page header.
- Web Links Create/Edit now uses Admin > Web Links > Create/Edit breadcrumbs and the shared page header.
- Redundant Return-to-list links were removed.
- Web Links now emits the correct Create or Edit browser title for the current editor mode.

## Compatibility

No migrations, dependency changes, module updates or theme updates are included. Existing forms, CSRF protection, routes, publication/access controls and authorization behavior are unchanged.

## Upgrade

Run `php bin/cms upgrade:check --from=0.2.0-alpha.51`, then the normal test/release checks.
