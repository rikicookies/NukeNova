# NovaNuke 0.2.0-alpha.26

Alpha.26 updates Wiki to 1.3.0 and connects published Wiki content to NovaNuke's optional global Search module.

## Upgrade

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.2.0 to 1.3.0.
4. Run `php bin/cms cache:clear`.

There is no database migration, reindex or core, theme or other module update.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiSearchProviderContractTest.php tests\Unit\SearchServiceTest.php tests\Unit\SearchProviderRegistryTest.php tests\Unit\LikePatternTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Enable both Wiki and Search and publish a public Wiki page containing a unique word.
- Search for that word at `/search` and confirm the Wiki page appears with a working canonical URL.
- Select **Wiki** from the content-type filter and confirm other content types disappear.
- Confirm drafts and future-dated or deleted Wiki pages never appear.
- Confirm guests cannot discover Member or VIP pages.
- Confirm registered non-VIP users see Member pages but not VIP pages.
- Confirm an active VIP user can discover VIP pages.
- Disable Search and confirm public and administrative Wiki routes still operate normally.
