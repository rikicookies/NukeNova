# NovaNuke 0.2.0-alpha.25

Alpha.25 updates Wiki to 1.2.0 with backlinks and missing-link discovery. It recognizes canonical internal links written as `[Title](/wiki/namespace:page)` without changing stored Markdown.

## Upgrade

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.1.0 to 1.2.0.
4. Run `php bin/cms cache:clear`.

There is no database migration or reindex and no core, theme or other module update.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiLinkIndexerTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Add `[Installation](/wiki/guides:installation)` to a published Wiki page and create the destination page.
- Open the destination and confirm the source appears under **Pages linking here**.
- Delete or rename the destination and confirm Admin → Wiki reports the missing target with one reference.
- Repeat the link in the same source and confirm the report still counts that source once.
- Confirm external URLs, image destinations, code examples and malformed Wiki paths are ignored.
- Confirm a public visitor does not see a Member- or VIP-only source in backlinks.

## Current limit

Links are parsed from current Markdown when the Wiki directory or page is requested. This is intentionally lightweight for small sites; a persistent link index remains a future scalability option.
