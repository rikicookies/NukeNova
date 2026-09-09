# NovaNuke 0.2.0-alpha.30

Alpha.30 updates Wiki to 1.7.0 with hierarchical namespace navigation and breadcrumbs.

## Upgrade

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.6.0 to 1.7.0.
4. Run `php bin/cms cache:clear`.

There is no database migration, core migration, theme update or new dependency.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiInputTest.php tests\Unit\WikiNavigationTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Publish pages at `start`, `guides:getting-started` and `guides:admin:users`.
- Open `/wiki` and confirm it shows the root page plus one `Guides` namespace card, without flattening nested pages.
- Open the Guides namespace and confirm its direct page plus the Admin child namespace appear.
- Open the nested page and use its breadcrumbs to return through Admin, Guides and Wiki.
- Confirm `/wiki?namespace=guides/../private` returns 404.
- While logged out, confirm a namespace containing only Member or VIP pages is not listed and its direct URL returns 404.
- Log in as an eligible user and confirm the permitted namespace becomes available.
