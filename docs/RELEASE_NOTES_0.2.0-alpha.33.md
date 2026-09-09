# NovaNuke 0.2.0-alpha.33

Alpha.33 consolidates Wiki 2.0.0 into a practical documentation workflow: safe editor helpers and preview, visible missing links, Wiki search/recent/map navigation, XML sitemap support, folder-to-namespace import and complete Markdown export.

## Upgrade

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.9.0 to 2.0.0.
4. Enable PHP's optional ZIP extension if **Export all .md** is needed.
5. Run `php bin/cms cache:clear`.

There is no database migration, core migration, theme update or new Composer dependency.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\WikiFolderImportTest.php tests\Unit\WikiArchiveTest.php tests\Unit\WikiEditorEnhancementTest.php tests\Unit\WikiLinkPresenterTest.php tests\Unit\WikiNavigationTest.php tests\Unit\WikiInputTest.php tests\Unit\WikiMarkdownFileTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\WikiSearchProviderContractTest.php tests\Unit\SitemapTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Open an existing Wiki page in Admin, insert a Wiki link and an attachment snippet, then confirm Preview renders them without saving or adding a revision.
- Link to a nonexistent valid Wiki path and confirm the public link has a red dashed missing marker and an authorized editor can create the page from its 404 screen.
- Visit `/wiki/map`, `/wiki/recent` and `/wiki/search?q=installation` as a guest, member and active VIP; confirm each account sees only its permitted published pages.
- Visit `/sitemap.xml` and confirm it contains public published Wiki URLs but no draft, future, Member or VIP URLs.
- Select or drag a folder containing `docs/start.md` and `docs/guides/installation.md`. Confirm the preview shows `docs:start` and `docs:guides:installation`.
- Confirm the folder import creates unpublished drafts, ignores non-`.md` files, skips an existing path and never changes its content.
- Try a malformed/traversal relative path and a selection larger than the host's `max_file_uploads`; confirm the import is rejected rather than partially trusted.
- Use **Export all .md**, inspect the ZIP and confirm `docs/guides/installation.md` contains the original Markdown source.
- Confirm the complete export reports a clear requirement message when PHP ZIP is disabled, while individual page export continues working.
