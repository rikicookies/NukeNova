# NovaNuke 0.2.0-alpha.28

Alpha.28 updates Wiki to 1.5.0 with safe single-file Markdown import and current-page Markdown export.

## Upgrade

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.4.0 to 1.5.0.
4. Run `php bin/cms cache:clear`.

There is no database migration and no core, theme or other module update.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiMarkdownFileTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\WikiInputTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Create a small UTF-8 file named `getting-started.md` with `# Getting Started` as its first heading.
- Import it from Admin → Wiki and confirm the editor opens with a suggested title and path.
- Confirm the page does not exist until **Save wiki page** is pressed and defaults to Draft/Public.
- Try a renamed PHP, binary file, empty file and file larger than 1 MB; confirm each is rejected.
- Save the imported draft and confirm a normal Wiki revision is created.
- Edit an existing Wiki page and select **Export .md**; confirm the downloaded bytes match its current Markdown source.
- Confirm a user without `wiki.edit` cannot import or export through manually constructed URLs.

## Current scope

This release deliberately handles one file at a time. Folder/ZIP exchange, filesystem synchronization, attachments and automatic publication remain separate future work.
