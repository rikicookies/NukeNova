# NovaNuke 0.2.0-alpha.32

Alpha.32 updates Wiki to 1.9.0 with Markdown attachment snippets and safe inline images.

## Upgrade

1. Preserve `.env`, `composer.lock`, `storage/installed.lock`, uploads and all of `storage/private/`.
2. Replace the application files with this release.
3. In Admin → Modules, update Wiki from 1.8.0 to 1.9.0.
4. Run `php bin/cms cache:clear`.

There is no migration, theme update or new Composer dependency.

## Focused tests

```bash
vendor\bin\phpunit tests\Unit\HtmlSanitizerTest.php tests\Unit\MarkdownRendererTest.php tests\Unit\ContentRendererTest.php tests\Unit\WikiAttachmentUploadTest.php tests\Unit\WikiAttachmentManagerTest.php tests\Unit\WikiModuleContractTest.php tests\Unit\ReleaseVersionTest.php
```

Then run the complete suite:

```bash
composer test
```

## Acceptance checks

- Edit a saved Wiki page and confirm each attachment shows a Markdown link snippet.
- Upload PNG, JPG and WebP images and confirm each also shows an inline-image snippet.
- Paste an image snippet into the page Markdown, save and confirm the image scales within the article.
- Check the same page with Default, Classic and NovaModern.
- Confirm a guest cannot load an inline image belonging to a Member, VIP, draft or future-scheduled page.
- Append `?inline=1` to a PDF or ZIP attachment URL and confirm it remains a forced download.
- Render Markdown containing `javascript:`, `//external-host/image.png`, `https://external-host/image.png` and an `onerror` attribute and confirm none survives as an image source.
