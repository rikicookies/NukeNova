# NovaNuke 0.2.0-alpha.31

Alpha.31 updates Wiki to 1.8.0 with safe, private page attachments.

## Upgrade

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.7.0 to 1.8.0.
4. Confirm `storage/private/wiki/` is writable by PHP.
5. Run `php bin/cms cache:clear`.

The Wiki module migration creates `wiki_attachments`. There is no core migration, theme update or new Composer dependency.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiAttachmentUploadTest.php tests\Unit\WikiAttachmentManagerTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\FileBackupTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Edit a saved Wiki page and upload a small PDF, Markdown file and JPG; confirm each appears publicly and downloads with its original name.
- Confirm the Activity Log records attachment creation and deletion.
- Try a `.php`, SVG, renamed MIME mismatch and file larger than 10 MB; confirm each is rejected.
- Attach a file to a draft and confirm an authorized Wiki editor can download it while a logged-out visitor receives 404.
- Publish Public, Member and VIP pages with attachments and confirm each audience boundary is enforced on a manually copied attachment URL.
- Delete one attachment with confirmation and confirm its URL stops working without affecting the page.
- Create a file backup and confirm it contains `storage/private/wiki/` and the stored attachment.
