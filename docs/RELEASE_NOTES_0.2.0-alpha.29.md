# NovaNuke 0.2.0-alpha.29

Alpha.29 updates Wiki to 1.6.0 with optional per-page discussions powered by the existing Comments module.

## Upgrade

1. Back up the database and preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.5.0 to 1.6.0 so its module migration runs.
4. Keep Comments 1.2.0 active if the site needs Wiki discussions.
5. Run `php bin/cms cache:clear`.

The Wiki migration adds `comments_enabled` to `wiki_pages` and `wiki_page_revisions`. Existing records default to disabled. There is no core migration, theme update or Comments update.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiInputTest.php tests\Unit\WikiRevisionTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\CommentTargetCheckingTest.php tests\Unit\CommentReactionsTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Update Wiki, edit a published public page, enable **Allow comments** and save it.
- Confirm its public route displays the shared comment form, threads and Like/Dislike controls.
- Submit a comment and confirm the configured moderation policy is respected.
- Disable comments on that page and confirm a manually constructed comment POST is rejected.
- Enable comments on Member and VIP Wiki pages and confirm unauthorized visitors cannot post to either target.
- Disable the Comments module and confirm Wiki remains operational with a temporary comments-unavailable notice.
- Re-enable Comments and confirm existing approved Wiki comments return.
- Restore a revision created with comments disabled and confirm discussion closes with the restored state.
