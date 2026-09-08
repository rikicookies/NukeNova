# NovaNuke 0.2.0-alpha.18

This cumulative development release includes the alpha.17 social foundation and adds consistent navigation across its public account areas.

## Highlights

- shared navigation for the member directory, profiles, Friends, Private Messages and Notifications;
- optional links remain hidden when their modules are disabled;
- guests receive only the public Members destination;
- no database or theme changes.

## Update from alpha.17

Back up first and preserve `.env`, `composer.lock`, `storage/installed.lock`, `storage/private/` and uploads. Replace the application files, then run:

```bash
composer install
php bin/cms cache:clear
```

No migration or module lifecycle action is required.

## Acceptance checks

- Open `/users` as a guest and confirm only Members appears in the social navigation.
- Sign in and confirm Members and My profile appear.
- Enable or disable Friends and Private Messages and confirm their links follow module state.
- Confirm Notifications and its unread count appear when that module is active.
- Visit a public profile, `/friends`, `/messages` and `/notifications` and verify the same navigation is available.
- Run `vendor\\bin\\phpunit tests\\Unit\\SocialNavigationTest.php tests\\Unit\\ReleaseVersionTest.php tests\\Unit\\ProfileTemplateTest.php` on Windows.

Blocks remain postponed technical debt and are unchanged by this release.
