# Wiki module

Wiki is an optional NovaNuke module for small documentation and knowledge sites. Installing or disabling it does not change Pages or other content modules.

## Paths and namespaces

Wiki paths use lowercase words, hyphens and colons. A colon represents a logical namespace:

```text
start
guides:getting-started
products:pool-finishes:maintenance
```

These appear at `/wiki/{path}`. Slashes, `..`, filesystem paths and empty segments are rejected. Namespaces are logical database values; they do not provide filesystem access.

Wiki 1.7.0 turns the public `/wiki` directory into a hierarchical browser. It lists root pages and only the immediate child namespaces; opening a namespace uses `/wiki?namespace=guides` and can continue through nested levels such as `guides:admin`. Breadcrumbs return to each parent namespace from both directories and individual pages.

The hierarchy is assembled from the same published, audience-authorized page list used by the directory. Empty restricted namespaces are therefore not disclosed to guests or members without access.

## Editing and publication

`wiki.edit` permits creating and updating drafts. `wiki.publish` permits publishing them. Super Administrators bypass individual permission assignment as elsewhere in NovaNuke.

When an authorized editor opens a valid nonexistent route, NovaNuke returns a real 404 page containing **Create this page**. The editor opens with the requested path prefilled. Visitors receive the same not-found page without the creation control.

Every save uses Markdown. The source passes through `MarkdownRenderer`, then `HtmlSanitizer`, before Twig receives safe HTML. Raw HTML inside Markdown and unsafe links are not executed or preserved.

Wiki 2.0.0 keeps editing in the same Admin form and adds three small workflow helpers:

- **Insert Wiki link** creates canonical `[label](/wiki/namespace:path)` Markdown;
- attachment buttons insert their ready-to-use link or safe inline-image snippet at the cursor;
- **Preview** opens sanitized output in a new tab without saving the page or creating a revision.

Preview requires `wiki.edit`, POST and CSRF, uses private no-store response headers, and deliberately forces draft validation so it cannot publish submitted content.

## Revision history

Wiki 1.1.0 records a complete snapshot after every successful creation or update. Existing Wiki 1.0.0 pages become revision 1 during the module update. Each snapshot keeps the path, title, Markdown, status, audience, publication time, actor and timestamp.

Editors can inspect a sanitized historical preview from the page editor or Wiki list. Restoring never deletes or rewrites old revisions: the selected snapshot becomes the current page and is immediately recorded as a new revision. Restoration requires confirmation and CSRF protection. A user without `wiki.publish` cannot restore a published snapshot.

Wiki 1.4.0 lets editors select any two snapshots from **History** and compare their metadata and Markdown line by line. Compared source is escaped rather than rendered. To keep shared-hosting memory predictable, NovaNuke rejects exceptionally large changed sections instead of attempting an unbounded diff.

## Markdown files

Wiki 1.5.0 accepts one `.md` file of up to 1 MB from Admin → Wiki. The upload must be valid UTF-8 plain text and pass extension, MIME, size and control-character checks. Import opens the normal editor with a suggested title and path; it does not create, save or publish a page until the editor reviews and submits it.

Editors can export the current version from the page editor. The download contains the original Markdown source rather than rendered HTML and receives a filename based on the Wiki path. Revision history remains the authoritative audit trail after an imported draft is saved.

Wiki 2.0.0 can also import a directory from Admin → Wiki. A selected tree such as:

```text
docs/
  start.md
  guides/
    installation.md
```

becomes `docs:start` and `docs:guides:installation`. The selected root directory is retained as the first namespace. The browser previews every resulting Wiki path before submission.

Directory import accepts at most 100 uploaded files and 20 MB total, while each Markdown file must still satisfy the normal 1 MB UTF-8 validation. It creates unpublished public drafts, skips paths already present (including soft-deleted records), ignores non-Markdown files and never merges or overwrites content. The action requires `wiki.edit`, POST, CSRF and explicit confirmation and is recorded in the Activity Log.

Current Chrome and Edge releases support both the folder picker and directory drag-and-drop used by this interface. PHP's `max_file_uploads`, `post_max_size` and `upload_max_filesize` may impose lower host limits; NovaNuke reports when the browser-selected count was truncated. The single-file import remains the portable fallback.

**Export all .md** builds one ZIP of current Markdown sources. Colon namespaces become directories, for example `guides:installation` becomes `guides/installation.md`. Export requires `wiki.edit`, is capped at 5,000 pages and requires PHP's optional ZIP extension. Attachments and revision history are not included in this source archive and remain covered by NovaNuke's normal file/database backups.

## Comments

Wiki 1.6.0 can enable comments independently on each page. When the optional Comments module is active, Wiki uses its existing threaded replies, guest policy, moderation, reports, Markdown or sanitized HTML bodies, and Like/Dislike reactions. Wiki does not duplicate comment tables or moderation logic.

Before Comments accepts a new Wiki comment, Wiki verifies that the target is published, discussion is enabled and the current visitor can view its Public/Member/VIP audience. The comments-enabled choice is included in revision snapshots and restored with them. Disabling Comments leaves Wiki pages available and shows a temporary-unavailability notice only on pages configured for discussion.

## Attachments

Wiki 1.8.0 lets an authorized editor attach PDF, text, Markdown, ZIP, PNG, JPG/JPEG or WebP files of up to 10 MB to a saved page. Upload and deletion are available in the existing Admin Wiki editor; attachments are deliberately separate from page revisions.

Files live under `storage/private/wiki/` with random server-generated names. A controller resolves every download and rechecks that the parent page is published and visible to the current Public/Member/VIP audience. Editors with `wiki.edit` may also download attachments while reviewing drafts. Original names are used only for the safe download header and interface label.

Wiki 1.9.0 displays a ready-to-copy Markdown link beside each attachment in the editor. PNG, JPG/JPEG and WebP files also receive an inline-image snippet:

```markdown
[Installation PDF](/wiki/attachments/15)
![Pool diagram](/wiki/attachments/16?inline=1)
```

Inline images pass through the normal full-content sanitizer and the same attachment authorization endpoint. Only local `/...` image sources are retained, preventing third-party tracking pixels; other attachment MIME types ignore inline mode and continue as downloads.

Upload validation checks PHP's upload result and provenance, actual size, allowed extension and server-inspected MIME. SVG and executable extensions are not allowed. Backups created by NovaNuke include the private Wiki attachment directory; preserve it when replacing application files.

## Internal links and backlinks

Use ordinary Markdown links with a canonical Wiki URL:

```markdown
[Installation guide](/wiki/guides:installation)
```

Wiki 1.2.0 shows published pages that link to the current page under **Pages linking here**. The list respects the source page audience, so a visitor never learns the title or path of a restricted source through backlinks.

Admin → Wiki also reports valid internal targets that do not exist and provides a prefilled **Create page** action. External links, images, links inside code, duplicates and invalid Wiki paths do not enter the graph.

On a rendered page, an authorized missing destination receives the `wiki-link-missing` class and a visible red dashed underline. Opening it keeps the normal 404 plus **Create this page** flow for editors; ordinary visitors never receive editor controls.

The graph is calculated from current Markdown without a migration or reindex. This keeps small installations simple; a persistent index can be added later if very large wikis require it.

## Navigation and discovery

Wiki 2.0.0 adds three small public views, each independent of the optional global Search module:

- `/wiki/map` renders a collapsible namespace tree similar to a documentation sitemap;
- `/wiki/recent` lists recently changed visible pages;
- `/wiki/search?q=...` searches current Wiki titles and Markdown source.

All three start from published pages authorized for the current guest, member or active VIP. They do not reveal inaccessible namespaces or draft metadata. Search uses bound MySQL parameters and a bounded result set.

## Global search and XML sitemap

When both Wiki 1.3.0 and the optional Search module are active, Wiki registers itself as a searchable content type. Global searches match published Wiki titles and Markdown source and link directly to the canonical Wiki path.

The provider applies publication time and Public/Member/VIP audience rules inside its database query. Disabling Search does not affect Wiki, and disabling Wiki simply removes Wiki results and its content-type filter.

When the SEO module builds `/sitemap.xml`, active Wiki 2.0.0 contributes canonical URLs for public pages that are published and whose scheduled publication time has arrived. Member/VIP pages, drafts and future pages are excluded.

## Audiences

Each published page may be visible to everyone, registered members or active VIP members. The module itself also retains NovaNuke's normal module-level audience. Both boundaries must allow the request.

## Current limits

- Folder import creates drafts only; it does not bulk publish, overwrite or merge existing pages.
- ZIP upload/import is not provided. Select or drag an extracted directory instead.
- Complete Markdown export contains current page sources, not attachments, revisions or database metadata.
- The directory picker and recursive drag-and-drop depend on Chromium browser APIs; use the single `.md` importer when unavailable.
- Wiki search intentionally uses bounded MySQL matching rather than an external search engine.
