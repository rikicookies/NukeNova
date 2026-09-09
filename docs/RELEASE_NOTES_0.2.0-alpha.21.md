# NovaNuke 0.2.0-alpha.21

Alpha.21 polishes the manually managed VIP system introduced in alpha.20. It does not add payments, plans, automatic renewals or new access rules.

## Included

- Public profiles show an active VIP badge but never the expiration timestamp.
- `/account/profile` shows the account owner whether VIP is active and the latest expiration time.
- Administrative lists use clear Public, Registered members, Active VIP members and Selected roles labels.
- Authenticated users receive a content-specific 403 message when News, Pages, Downloads or Web Links access is denied.
- Entitlement period logic treats the expiration instant as expired and rejects revoked grants.

## Update from alpha.20

1. Back up the site and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files.
3. Update Pages 1.5.1, News 1.8.1, Downloads 1.4.1 and Web Links 1.2.1 under Admin → Modules.
4. Run `php bin/cms cache:clear`.

No migration or theme update is required.

## Focused tests

Run from the project root:

```console
vendor\bin\phpunit tests\Unit\VipEntitlementTest.php tests\Unit\ProfileTemplateTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the normal unit suite before deployment:

```console
composer test
```

## Acceptance checks

1. Grant a test user one day of VIP from Admin → Users.
2. Confirm the public profile shows `VIP member` without a date.
3. Sign in as that user and confirm `/account/profile` shows an expiration date in the configured site timezone.
4. Confirm News, Pages, Downloads and Web Links admin lists show descriptive audience labels.
5. Confirm a normal signed-in member receives a clear 403 message for VIP content and cannot use its protected download/link action.
6. Revoke VIP and confirm the badge disappears and VIP content becomes inaccessible immediately.

## Deferred

Blocks remain postponed technical debt and were not changed in this release. Wiki and paid subscription features are not included.
