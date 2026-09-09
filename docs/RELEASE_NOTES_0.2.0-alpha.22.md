# NovaNuke 0.2.0-alpha.22

Alpha.22 makes manually managed VIP access easier to monitor from the existing user administration screen. It does not introduce payments, subscriptions, plans or automatic renewal.

## Included

- Admin → Users displays Active VIP, Expired/revoked or Never VIP for every account.
- Active periods show their UTC expiration and warn when seven days or less remain.
- Filters quickly isolate active, inactive or never-VIP accounts.
- An invalid filter value safely falls back to the complete user list.

The individual user editor remains the place to grant, extend or revoke VIP. Existing authorization, CSRF protection and Activity Log behavior are unchanged.

## Update from alpha.21

1. Back up the site and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files.
3. Run `php bin/cms cache:clear`.

No migration, module update or theme update is required.

## Focused tests

```console
vendor\bin\phpunit tests\Unit\VipAdminListTest.php tests\Unit\VipEntitlementTest.php tests\Unit\ReleaseVersionTest.php
```

## Acceptance checks

1. Open Admin → Users and confirm existing accounts display a VIP status.
2. Test All, Active VIP, Expired/revoked and Never VIP filters.
3. Grant VIP to a test account and confirm it moves into Active VIP without changing its role or account status.
4. Confirm an active period ending within seven days displays the warning.
5. Revoke the grant and confirm the account appears under Expired/revoked.
6. Open `/admin/users?vip=invalid` and confirm it safely shows the unfiltered list.

## Deferred

Blocks remain postponed technical debt. Payments, recurring subscriptions and Wiki are not part of this release.
