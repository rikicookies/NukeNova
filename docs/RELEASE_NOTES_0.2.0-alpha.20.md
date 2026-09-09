# NovaNuke 0.2.0-alpha.20

This cumulative development release adds manual, expiring VIP access and consistent audience enforcement to the private-site and social foundation from alpha.19.

## Highlights

- Super Administrator grant, extension and revocation of time-limited VIP;
- public, guest, member and VIP audiences for modules and blocks;
- VIP-aware Pages, News, Downloads and Web Links;
- protected lists, details, comments, search, file delivery and external redirects;
- public-only RSS and sitemap exposure for restricted content.

VIP is an entitlement on the existing account. Expiration removes exclusive access but does not change the account, roles, profile or normal member content. Payments, plans and automatic renewals are not included.

## Update from alpha.19

Back up first and preserve `.env`, `composer.lock`, `storage/installed.lock`, `storage/private/` and uploads. Replace the application files, then run:

```bash
composer install
php bin/cms migrate
php bin/cms cache:clear
```

From Admin → Modules update Pages to 1.5.0, News to 1.8.0, Downloads to 1.4.0 and Web Links to 1.2.0. No theme update is required.

## Acceptance checks

- Grant VIP for one day and confirm the active expiration appears in user administration.
- Confirm public content remains visible to guests.
- Confirm member content is hidden from guests but visible to a normal signed-in member.
- Confirm VIP content is absent from lists and searches for guests and normal members, but visible to the VIP account.
- Manually request a VIP Page, News article, Download delivery and Web Link visit as a non-VIP user; confirm access remains denied.
- Confirm restricted News is absent from RSS and restricted News/Pages are absent from the sitemap.
- Revoke VIP and confirm access ends immediately without changing the account or roles.
- Run `composer test`, `composer test:integration`, `php bin/cms migrate:status`, `php bin/cms release:check` and `php bin/cms production:check` on the target environment.
