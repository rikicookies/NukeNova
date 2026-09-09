# NovaNuke 0.2.0-alpha.23

Alpha.23 introduces Wiki 1.0.0 as an optional module. It is suitable for small documentation sets while leaving room for later revision history, backlinks and Markdown file exchange.

## Install

1. Back up the site and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files.
3. Open Admin → Modules, install Wiki 1.0.0 and enable it.
4. Assign `wiki.edit` and `wiki.publish` to any appropriate roles.
5. Run `php bin/cms cache:clear`.

The Wiki migration runs during module installation. No core or theme migration is required.

## Focused tests

```console
vendor\bin\phpunit tests\Unit\WikiInputTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\ReleaseVersionTest.php
```

## Acceptance checks

1. Install and enable Wiki, then open `/wiki`.
2. Create and publish `guides:getting-started`; confirm `/wiki/guides:getting-started` renders Markdown.
3. Add script, iframe and `javascript:` examples and confirm they cannot execute or retain unsafe links.
4. Open a valid missing path while authorized and confirm **Create this page** prefills that path.
5. Repeat as a visitor and confirm the creation control is absent while the response remains 404.
6. Save a draft and confirm it is absent publicly.
7. Test Member and VIP audiences with guest, normal-member and expired-VIP accounts.
8. Delete a test page and confirm direct access no longer finds it.

## Deferred Wiki work

- revision history and rollback;
- backlinks and missing-link discovery;
- `.md` import/export and safe bulk synchronization;
- Search, comments and attachments integration.

Blocks remain postponed and were not changed.
