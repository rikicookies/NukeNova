# NovaNuke 0.2.0-alpha.19

This cumulative development release adds private-site access and manual account provisioning to the alpha.18 social foundation.

## Highlights

- optional authenticated-only site mode;
- public registration remains an independent setting;
- Super Administrator account creation with role assignment;
- optional mandatory change of an initial password;
- secure temporary-password reset for existing users;
- active-session and outstanding-token revocation after an administrative reset.

## Update from alpha.18

Back up first and preserve `.env`, `composer.lock`, `storage/installed.lock`, `storage/private/` and uploads. Replace the application files, then run:

```bash
composer install
php bin/cms migrate
php bin/cms cache:clear
```

No module or theme lifecycle action is required.

## Acceptance checks

- Enable Private site under Admin → Registration settings and confirm anonymous content requests redirect to login.
- Confirm authenticated users retain normal site access and account recovery remains reachable.
- Keep public registration disabled and create an account at `/admin/users/create`.
- Confirm a non-Super-Administrator cannot access account creation or administrative password reset.
- Create an account requiring a password change and confirm unrelated application routes redirect to Account settings.
- Change the initial password and confirm normal access is restored after signing in again.
- Reset an existing user's password and confirm their old session and old recovery links stop working.
- Run `vendor\\bin\\phpunit tests\\Unit\\PrivateSiteAccessPolicyTest.php tests\\Unit\\AdminUserCreationTest.php tests\\Unit\\PasswordChangeAccessPolicyTest.php tests\\Unit\\AdminPasswordResetTest.php tests\\Unit\\ReleaseVersionTest.php` on Windows.

VIP access levels, expiring memberships, plans and payments remain intentionally deferred until their product rules are designed.
