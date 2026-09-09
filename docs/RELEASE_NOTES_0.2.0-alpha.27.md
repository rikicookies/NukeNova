# NovaNuke 0.2.0-alpha.27

Alpha.27 updates Wiki to 1.4.0 with an administrative comparison between two stored revisions of the same page.

## Upgrade

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.3.0 to 1.4.0.
4. Run `php bin/cms cache:clear`.

There is no database migration and no core, theme or other module update.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiRevisionComparatorTest.php tests\Unit\WikiRevisionTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Edit and save a Wiki page at least twice so its history contains different Markdown.
- Open History, select an older revision under **From** and a newer revision under **To**, then compare.
- Confirm changed title, path, status and audience values appear in the metadata table.
- Confirm removed lines appear only in the old column and added lines only in the new column.
- Put HTML-like text or a script tag in Markdown, save two revisions and confirm comparison displays it as text rather than executing it.
- Manually request a comparison using a revision from another page and confirm it returns not found.
- Confirm users without `wiki.edit` cannot access comparison URLs.

## Resource boundary

NovaNuke removes shared prefixes and suffixes before calculating the line diff. Comparisons exceeding 20,000 combined lines or 250,000 changed-line pairs are rejected with a safe validation response, keeping memory use predictable on shared hosting.
