# NovaNuke 0.2.0-alpha.24

Alpha.24 updates Wiki to 1.1.0 with transactional revision history and non-destructive restoration.

## Update from alpha.23

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files.
3. Open Admin → Modules and update Wiki to 1.1.0.
4. Run `php bin/cms cache:clear`.

The Wiki migration creates `wiki_page_revisions` and stores each existing page as revision 1. No core or theme migration is required.

## Focused tests

```console
vendor\bin\phpunit tests\Unit\WikiRevisionTest.php tests\Unit\WikiInputTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\ReleaseVersionTest.php
```

## Acceptance checks

1. Update Wiki and confirm every existing page has revision 1 under **History**.
2. Edit a page twice and confirm revision numbers increase without losing earlier snapshots.
3. Inspect an older revision containing unsafe HTML and confirm it cannot execute.
4. Restore an older draft and confirm restoration creates a new latest revision.
5. Attempt to restore a published revision as a role with only `wiki.edit`; the server must reject it.
6. Restore it as a user with `wiki.publish` and confirm the public page matches the snapshot.
7. Change a page path, restore an older path already used by another page and confirm the restore fails without partial changes.

## Deferred Wiki work

- side-by-side revision comparison;
- backlinks and missing-link discovery;
- `.md` import/export and safe bulk synchronization;
- Search, comments and attachments integration.

Blocks remain postponed and were not changed.
