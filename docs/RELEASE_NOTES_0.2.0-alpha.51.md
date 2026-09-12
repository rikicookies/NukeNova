# NovaNuke 0.2.0-alpha.51

Alpha.51 continues the Admin Experience pass by bringing the remaining Users and Roles editor screens into the shared navigation/header pattern introduced across the Admin.

## Changed

- Create account now uses Admin > Users > Create account breadcrumbs and the shared page header.
- Manage user now uses Admin > Users > username breadcrumbs and the shared page header.
- Role permissions now use Admin > Roles > role breadcrumbs and the shared page header.
- Redundant Return to users/roles links were removed.

## Compatibility

No migrations, dependency changes, module updates or theme updates are included. Existing forms, CSRF protection and authorization behavior are unchanged.

## Upgrade

Run `php bin/cms upgrade:check --from=0.2.0-alpha.50`, then the normal test/release checks.
