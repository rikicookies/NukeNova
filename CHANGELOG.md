# Changelog

All notable NovaNuke changes will be documented here.

## [0.2.0-alpha.33] - 2026-09-09

### Added

- Wiki 2.0.0 safe unsaved Markdown preview, internal-link helper and attachment link/image insertion controls in the existing editor.
- Visible styling for internal Wiki links whose authorized destination does not exist yet.
- Audience-aware `/wiki/search`, `/wiki/recent` and DokuWiki-style `/wiki/map` discovery views.
- Public published Wiki pages in NovaNuke's extensible XML sitemap.
- Folder drag-and-drop/import that converts directories into colon-separated namespaces and creates unpublished drafts.
- Complete Wiki source export as a ZIP whose directories mirror namespaces.

### Security

- Folder import requires `wiki.edit`, POST, CSRF and explicit confirmation; validates count, total size, UTF-8 Markdown and normalized relative paths; rejects traversal and duplicates; never overwrites existing or deleted paths; and records an Activity Log entry.
- Import detects when PHP's `max_file_uploads` truncates a browser selection. Non-Markdown files are ignored and imported pages remain unpublished for manual review.
- Preview uses the established sanitized Markdown pipeline, performs no save and returns private no-store output.
- Public Wiki search, recent changes and map apply publication and Public/Member/VIP audience rules. The XML sitemap includes only public published pages.
- Archive entry paths are validated, archive responses are private/no-store, temporary files are removed after delivery and exports are capped at 5,000 pages.

### Compatibility

- Update Wiki from 1.9.0 to 2.0.0 under Admin → Modules. No database migration, core migration, theme update or new Composer dependency is required.
- PHP's ZIP extension is optional and required only by **Export all .md**. Browser folder picking and directory drag-and-drop are intended for current Chromium-based browsers; the existing single-file import remains available elsewhere.

## [0.2.0-alpha.32] - 2026-09-09

### Added

- Wiki 1.9.0 ready-to-copy Markdown snippets for every attachment.
- Safe inline display for attached PNG, JPG/JPEG and WebP images using the existing permission-aware attachment endpoint.
- Responsive full-content image styling shared by all bundled themes.

### Security

- Inline mode remains behind the parent Wiki page's publication and Public/Member/VIP authorization checks.
- Only server-inspected PNG, JPEG and WebP MIME types can render inline; all other attachment types retain forced-download behavior.
- The HTML sanitizer now permits local image markup in full content only, removes event/style attributes, rejects executable, remote and protocol-relative sources, and adds lazy decoding hints.

### Compatibility

- Update Wiki from 1.8.0 to 1.9.0 under Admin → Modules. No database migration, core migration, theme update or new dependency is required.

## [0.2.0-alpha.31] - 2026-09-09

### Added

- Wiki 1.8.0 page attachments managed from the existing Wiki editor.
- Permission-aware attachment downloads and inclusion of private Wiki files in application backups.
- PDF, text, Markdown, ZIP, PNG, JPG/JPEG and WebP support with a 10 MB per-file limit.

### Security

- Attachments are stored under `storage/private/wiki` with cryptographically generated server filenames and delivered only through a controller.
- Upload and deletion require `wiki.edit`, POST and CSRF; deletion also requires explicit confirmation and both actions enter the Activity Log.
- Extension, server-inspected MIME, actual size, PHP upload provenance and path containment are validated. Executable PHP and SVG are not accepted.
- Public download authorization rechecks publication time and Public/Member/VIP access; authorized Wiki editors may inspect attachments on drafts.

### Compatibility

- Update Wiki from 1.7.0 to 1.8.0 under Admin → Modules to create `wiki_attachments`.
- No core migration, theme update or new dependency is required. Preserve `storage/private/wiki/` during future updates.

## [0.2.0-alpha.30] - 2026-09-09

### Added

- Wiki 1.7.0 hierarchical namespace directory at `/wiki?namespace=...`.
- Immediate child namespace cards and breadcrumbs on Wiki directories and public pages.

### Security

- Namespace navigation is constructed only from published pages already filtered for the current visitor's Public/Member/VIP access.
- Query namespaces use the same strict lowercase, hyphen and colon grammar as Wiki paths; malformed and inaccessible empty namespaces return 404.
- Wiki page saves now reject namespace values longer than their database column.

### Compatibility

- Update Wiki from 1.6.0 to 1.7.0 under Admin → Modules. No database migration, core migration, theme update or new dependency is required.

## [0.2.0-alpha.29] - 2026-09-09

### Added

- Wiki 1.6.0 per-page **Allow comments** setting stored in current pages and revision snapshots.
- Optional Wiki integration with the shared threaded Comments module, including replies, moderation, reports and Like/Dislike reactions.
- Comment availability state when a Wiki page allows discussion but Comments is disabled.

### Security

- Comment target acceptance verifies that the Wiki page is published, comments are enabled and the current visitor may view its Public/Member/VIP audience.
- Wiki reuses Comments' existing POST, CSRF, sanitization, moderation and rate-limiting boundaries.
- Restoring a Wiki revision also restores its historical comments-enabled setting.

### Compatibility

- Update Wiki from 1.5.0 to 1.6.0 under Admin → Modules to add `comments_enabled` to Wiki pages and revisions.
- Comments remains optional; no core migration, theme update, Comments update or change to other modules is required.

## [0.2.0-alpha.28] - 2026-09-09

### Added

- Wiki 1.5.0 import of a `.md` file into the existing editor as an unsaved draft.
- Suggested title from the first Markdown heading and suggested path from the safe filename.
- Current-page export containing the original Markdown source with a portable path-based filename.

### Security

- Import requires `wiki.edit`, POST and CSRF and validates upload status, `.md` extension, MIME, exact size, 1 MB limit and UTF-8 text.
- Uploaded Markdown is never executed or published automatically; the authorized editor must review and explicitly save it.
- Export requires `wiki.edit` and uses safe attachment, content-type, no-sniff and private no-store headers.

### Compatibility

- Update Wiki from 1.4.0 to 1.5.0 under Admin → Modules. No migration, core migration, theme update or new dependency is required.

## [0.2.0-alpha.27] - 2026-09-09

### Added

- Wiki 1.4.0 comparison between any two revisions belonging to the same page.
- Revision selector in Wiki history, metadata comparison and escaped line-by-line Markdown differences.
- Added, removed and unchanged line markers without executing historical content.

### Security

- Comparison requires `wiki.edit` and resolves both revision identifiers under the requested Wiki page.
- Diff output uses normal Twig escaping and never renders compared Markdown as executable HTML.
- Line and changed-pair limits prevent unusually large comparisons from exhausting application memory.

### Compatibility

- Update Wiki from 1.3.0 to 1.4.0 under Admin → Modules. No migration, core migration, theme update or change to other modules is required.

## [0.2.0-alpha.26] - 2026-09-08

### Added

- Wiki 1.3.0 provider for NovaNuke's extensible global Search module.
- Wiki title and Markdown-source matching with direct links to canonical Wiki paths.
- Wiki content-type filter in `/search` whenever both Wiki and Search are active.

### Security

- Search returns only published Wiki pages whose publication time has arrived.
- Public, registered-member and active-VIP audiences are enforced before Wiki results are returned.
- Search terms remain bound parameters and SQL LIKE metacharacters use the shared escaping helper.

### Compatibility

- Update Wiki from 1.2.0 to 1.3.0 under Admin → Modules. No migration, core migration, reindex or theme update is required.
- Wiki remains independent: without the optional Search module, all Wiki routes continue to work normally.

## [0.2.0-alpha.25] - 2026-09-08

### Added

- Wiki 1.2.0 backlinks on public pages using ordinary internal Markdown links.
- Administrative missing-link report with reference counts and direct page-creation shortcuts.
- Safe link extraction from the CommonMark document tree, including duplicate removal and strict Wiki path validation.

### Security

- Public backlinks include only published source pages the current visitor is authorized to view.
- External URLs, images, code spans and invalid or traversal-like Wiki paths are excluded from the link graph.

### Compatibility

- Update Wiki from 1.1.0 to 1.2.0 under Admin → Modules. No migration, reindex, core migration or theme update is required.
- Existing Markdown links are discovered immediately; database-backed indexing is deferred for larger installations.

## [0.2.0-alpha.24] - 2026-09-08

### Added

- Wiki 1.1.0 transactional revision history for new and existing wiki pages.
- Administrative history list and sanitized historical Markdown preview.
- Non-destructive restoration that records the restored snapshot as a new current revision.
- Revision author, number, timestamp, publication status and audience metadata.

### Security

- Page updates lock the current Wiki record and save its revision in the same database transaction.
- Revision restoration requires POST, CSRF, explicit confirmation and `wiki.edit`; restoring published content also requires `wiki.publish`.
- Historical content passes through the normal Markdown and HTML-sanitization pipeline before display.

### Compatibility

- Update Wiki from 1.0.0 to 1.1.0 under Admin → Modules to create and backfill its revision table.
- No core migration, theme update or change to other modules is required.

## [0.2.0-alpha.23] - 2026-09-08

### Added

- Optional Wiki 1.0.0 module with Markdown-only content and DokuWiki-style colon-separated namespaces.
- Public wiki directory, friendly page routes, drafts, publication workflow and Public/Member/VIP audiences.
- Permission-aware missing-page screen that links authorized editors directly to a prefilled editor.
- `wiki.edit` and `wiki.publish` permissions, administrative navigation, safe deletion and Activity Log entries.
- Basic English and Spanish Wiki interface catalogues.

### Security

- Wiki paths use a strict lowercase segment grammar and reject slashes, traversal and arbitrary filesystem paths.
- Markdown passes through the existing safe renderer and HTML sanitizer; embedded PHP or administrator-provided executable code is never evaluated.
- Every administrative write requires server-side authorization and CSRF validation.

### Compatibility

- Install and enable Wiki manually from Admin → Modules; its own migration creates `wiki_pages`.
- Existing modules, themes and database tables are unchanged. File-based Markdown import/export is deferred.

## [0.2.0-alpha.22] - 2026-09-08

### Added

- VIP status and latest expiration directly in Admin → Users.
- Filters for all users, active VIP, expired or revoked VIP, and accounts that have never received VIP.
- Seven-day expiration warning beside active VIP accounts.
- Focused coverage for filter whitelisting, latest-entitlement selection and administrative status rendering.

### Security

- The VIP query filter is resolved through a fixed server-side allowlist and is never inserted from raw request input.
- Existing Super Administrator authorization remains required for granting, extending or revoking VIP.

### Compatibility

- No database migration, module update or theme update is required from alpha.21.
- VIP remains manual; payments, plans and automatic renewal are not included.

## [0.2.0-alpha.21] - 2026-09-08

### Added

- Active VIP badge on public profiles without exposing the entitlement expiration date.
- Private account status showing whether VIP is active and when its latest period expires.
- Boundary tests for active, expired and revoked VIP entitlements.

### Changed

- Pages 1.5.1, News 1.8.1, Downloads 1.4.1 and Web Links 1.2.1 show descriptive audience labels in their administrative lists.
- Signed-in users receive a clear content-specific access message instead of a bare `Forbidden` response; guests are still redirected to sign in.

### Compatibility

- No database migration or theme update is required from alpha.20.
- Update the four content modules from Admin → Modules and clear application caches.

## [0.2.0-alpha.20] - 2026-09-08

### Added

- Manual, time-limited VIP entitlements that a Super Administrator can grant, extend or revoke from user administration.
- Audience selection for modules and blocks: public, guests, registered members or active VIP members.
- VIP content access for Pages 1.5.0, News 1.8.0, Downloads 1.4.0 and Web Links 1.2.0.

### Security

- Module audiences are enforced against their public HTTP routes rather than only hidden from menus.
- Content audiences are enforced at list, detail, search and sensitive action boundaries, including private download delivery and external-link redirects.
- Expired or revoked VIP grants stop authorizing access immediately without changing the user's account or roles.
- RSS and sitemap output expose only public News and Pages content; user-submitted Web Links cannot assign themselves a restricted audience.

### Compatibility

- Run core migrations to create `user_entitlements` and add the audience columns for modules and blocks.
- Update Pages to 1.5.0, News to 1.8.0, Downloads to 1.4.0 and Web Links to 1.2.0 from Admin → Modules.
- Existing modules, blocks and content receive public defaults. No theme update is required.
- Payments, plans, recurring subscriptions and automatic purchasing remain out of scope.

## [0.2.0-alpha.19] - 2026-09-08

### Added

- Optional private-site mode that requires authentication for site content while preserving login, registration policy, recovery, verification, health and installer access.
- Super Administrator account creation with validated credentials, one assigned role and an optional mandatory initial-password change.
- Super Administrator temporary-password reset for existing accounts.

### Security

- Private-site access is enforced by the HTTP Kernel rather than navigation visibility.
- Mandatory password changes cannot be bypassed by manually entering another application URL.
- Administrative password resets revoke active sessions and invalidate outstanding password-reset and email-change tokens.
- Account creation and password resets require server-side authorization, CSRF protection and Activity Log entries; plaintext passwords are never logged.

### Compatibility

- Run the core migration to add `users.must_change_password`; existing accounts receive the safe default `0`.
- No module or theme update is required. VIP, plans, payments and expiring memberships are not part of this release.

## [0.2.0-alpha.18] - 2026-09-08

### Added

- Reusable social navigation shared by the member directory, public and editable profiles, Friends, Private Messages and Notifications.
- Optional social destinations appear only while their modules are active; guests only receive the public member-directory link.

### Compatibility

- No database migration, module update or theme update is required from 0.2.0-alpha.17.
- Blocks remain postponed technical debt and are unchanged by this release.

## [0.2.0-alpha.17] - 2026-09-08

### Added

- Public paginated member directory at `/users`, respecting account status and profile visibility without selecting email addresses.
- Optional Friends 1.0.0 module with requests, acceptance, decline, removal and contact blocking.
- Discreet profile actions for friendship management and composing a private message after friendship is accepted.
- Comments 1.2.0 Like/Dislike reactions with one toggleable reaction per registered user and visible totals for guests.
- Notifications 1.1.0 friendship-request and acceptance notifications; Friends remains independent when Notifications is disabled.
- Optional profile website and location fields, plus modular public counts for published News, approved Comments and accepted Friends.
- Typed `profile.actions.building` and `profile.statistics.building` extension points for optional modules.

### Changed

- News 1.7.0 contributes its public author count without coupling profiles to the News schema.
- Private Messages 1.2.0 from alpha.16 is included in this cumulative package and supports Markdown or sanitized HTML bodies.

### Security

- Friendship and reaction mutations require authentication, POST and CSRF validation; relationship ownership is enforced in SQL.
- Blocking removes an existing friendship or pending request and prevents new friendship operations in either direction.
- Profile websites accept only absolute HTTP/HTTPS URLs without embedded credentials and open with `noopener noreferrer`.
- Account anonymization removes website and location values.

### Compatibility

- Run the core migration, update Private Messages to 1.2.0, Comments to 1.2.0, Notifications to 1.1.0 and News to 1.7.0, then install and enable Friends 1.0.0.
- No theme update is required.

## [0.2.0-alpha.16] - 2026-09-08

### Added

- Private Messages 1.2.0 supports Markdown by default or explicitly selected sanitized HTML for new messages and replies.
- Conversation bodies and sent-message excerpts are derived from the restricted shared message renderer.

### Security

- Stored message source is never trusted as output; unsafe HTML, protocols and unsupported elements are removed before Twig receives trusted markup.

### Compatibility

- Update Private Messages from Admin → Modules; existing messages are assigned `markdown`.

## [0.2.0-alpha.15] - 2026-09-07

### Added

- User biographies support Markdown by default or explicitly selected sanitized HTML.
- Public profiles render biographies through the restricted shared profile rather than trusting stored source.

### Compatibility

- Run the core migration; existing biographies are assigned `markdown`.

## [0.2.0-alpha.14] - 2026-09-07

### Added

- Comments 1.1.0 uses Markdown by default and permits explicitly selected sanitized HTML for comments, replies and limited-time edits.
- Content profiles now enforce narrower tag sets for descriptions, comments, profiles and messages.

### Security

- Comment source is validated for visible rendered text and never exposed as trusted markup before the restricted shared rendering pipeline.

### Compatibility

- Update Comments from Admin → Modules; existing plain-text comments are assigned `markdown`.

## [0.2.0-alpha.13] - 2026-09-07

### Added

- Web Links 1.1.0 supports sanitized HTML or Markdown descriptions in administration and moderated user submissions.
- Public catalogue excerpts and detail views consume safely rendered descriptions.

### Compatibility

- Update Web Links from Admin → Modules; existing descriptions retain the `html` format.

## [0.2.0-alpha.12] - 2026-09-07

### Added

- Downloads 1.3.0 supports independent sanitized HTML or Markdown formats for descriptions and requirements.
- Download catalog excerpts are derived from safely rendered content; detail views render both enriched fields through the shared content service.

### Compatibility

- Update Downloads from Admin → Modules; existing descriptions and requirements retain the `html` format.

## [0.2.0-alpha.11] - 2026-09-07

### Added

- News 1.6.0 supports independent sanitized HTML or Markdown formats for summaries and full article content.
- News lists, detail metadata and RSS consume safely rendered content without exposing stored source as trusted markup.

### Compatibility

- Update News from Admin → Modules; existing summaries and articles retain the `html` format.

## [0.2.0-alpha.10] - 2026-09-07

### Added

- Shared `ContentRendererInterface`, explicit HTML/Markdown formats and reusable content profiles for current and future modules.
- Pages 1.4.0 stores editable source with an explicit format and renders it through the safe shared pipeline.

### Security

- HTML is sanitized at output time; Markdown strips embedded HTML, rejects unsafe links and is sanitized after conversion.

### Compatibility

- Update Pages from Admin → Modules to add `content_format`; existing pages remain sanitized HTML.

## [0.2.0-alpha.9] - 2026-09-07

### Added

- Discreet frontend Delete controls for authorized News, Pages, Downloads and Web Links editors.
- Shared confirmation UI and strict allowlisted post-delete return paths.

### Security

- Deletes reuse the existing POST routes, CSRF validation, server-side authorization and activity logging; hidden controls never replace permission checks.

### Compatibility

- No migration, module lifecycle action or theme update is required from alpha.8.

## [0.2.0-alpha.8] - 2026-09-07

### Changed

- Recommended Web Links open in a new tab with `noopener`, `noreferrer` and `nofollow` protections.
- External Downloads open in a new tab; locally stored downloads retain normal browser download behavior.

### Compatibility

- No migration, module lifecycle action or theme update is required from alpha.7.

## [0.2.0-alpha.7] - 2026-09-07

### Fixed

- NovaModern content cards, comments, forms, tables, module cards and status messages no longer inherit mismatched dark-theme foreground/background colors from the shared stylesheet.
- Public detail views use the available center-column width instead of leaving misleading empty space beside block columns.
- Desktop content and block column gaps are narrower and block stacks use consistent spacing.

### Changed

- NovaModern is updated to 1.1.0 with named light-palette tokens and explicit contrast states.
- Responsive block order is documented as deferred technical debt; provider and placement logic remain untouched.

### Compatibility

- No database migration or module update is required from alpha.6.
- Existing NovaModern installations must use Update in Admin → Themes to publish the 1.1.0 stylesheet.

## [0.2.0-alpha.6] - 2026-09-07

### Added

- NovaModern 1.0.0 as an optional third theme for both the public site and administration panel.
- Responsive left navigation that collapses to icons on desktop, behaves as a mobile drawer and remembers its desktop state locally.
- A bundled same-origin SVG icon sprite with no remote font or JavaScript dependency.
- Permission-filtered administrative navigation available on every Admin screen and grouped into Overview, Content, Resources, Community, Appearance and System.
- Optional backward-compatible `icon` and `group` metadata for `admin.menu.building` entries.
- Focused tests for the NovaModern Admin layout, navigation behavior, icon metadata and public block columns.

### Changed

- NovaModern visually organizes existing dashboard metrics, recent activity, tables and administrative links without changing their data or permissions.
- Default and Classic remain installed and unchanged.

### Compatibility

- No database migration or module update is required from alpha.5.
- NovaModern must be installed and activated manually from Themes after copying the release files.

## [0.2.0-alpha.5] - 2026-09-07

### Added

- Discreet, permission-aware Edit shortcuts on public News, Pages, Downloads and Web Links detail pages.
- A shared Twig component for contextual content actions, compatible with Default and Classic.
- Unit coverage for action visibility and the permission-to-editor route contracts.
- A dedicated Known Issues and Technical Debt document.

### Security

- Frontend shortcuts are calculated through `AuthorizationService`; existing Admin controllers continue to enforce the same permissions server-side.
- This checkpoint adds no frontend mutation route and makes no changes to Delete behavior.

### Compatibility

- No database migration or module/theme update is required from alpha.4.
- Blocks are explicitly postponed and do not block subsequent feature checkpoints.

## [0.2.0-alpha.4] - 2026-09-07

### Fixed

- Dynamic Polls and Statistics blocks no longer initialize Twig before the `blocks` global can be registered.
- Menus are registered first and block regions use a mutable, read-only-to-templates container populated safely after Twig initialization.
- Dynamic provider isolation remains active without causing `Unable to add global \"blocks\"` errors.

### Added

- Unit coverage for mutating registered block regions after Twig has initialized.

### Compatibility

- No database, module or theme update is required from alpha.3.
- Module API 1.0 remains unchanged.

## [0.2.0-alpha.3] - 2026-09-07

### Fixed

- Default and Classic layouts render active left/right sidebar blocks around every public child page instead of only Home.
- Home templates no longer duplicate sidebar output now owned by the shared layout.
- Application route/module/block boot runs inside the HTTP error boundary, producing a logged response instead of an uncaught bootstrap failure.
- MIME validation uses a delimiter that does not conflict with the allowed `#` character and no longer raises a PHP warning.

### Added

- Strict-Twig coverage proving both bundled layouts render left and right blocks exactly once while retaining route content.

### Changed

- Default and Classic advance to 1.8.0 and require a controlled theme update to republish CSS assets.

### Compatibility

- No database or module migration is required from alpha.2.
- Module API 1.0 remains unchanged.

## [0.2.0-alpha.2] - 2026-09-07

### Fixed

- The Markdown security test now separates stripped block HTML from subsequent valid Markdown, matching CommonMark parsing rules.
- Exceptions from trusted dynamic-block providers are isolated so one failed block cannot return a site-wide 500 response.
- Dynamic-block failure logs redact credential-like values and identify the block type and ID.
- Poll and Statistics block templates tolerate incomplete values in Twig strict mode.

### Changed

- Polls 1.1.0 and Statistics 1.2.0 add idempotent migrations that restore deleted default blocks as disabled.
- Existing dynamic blocks are neither duplicated nor overwritten during module updates.

### Compatibility

- No core database migration or theme update is required.
- Polls and Statistics require controlled updates from the Modules panel.
- Module API 1.0 remains unchanged.

## [0.2.0-alpha.1] - 2026-09-04

### Added

- Administrator-selectable sanitized HTML or Markdown content for editable blocks.
- CommonMark rendering with embedded HTML stripped, unsafe links disabled and final HTML sanitization.
- Unit coverage for ordinary Markdown, embedded HTML and dangerous link handling.

### Changed

- Markdown source remains editable and is rendered only for public block output.
- Trusted module-provided dynamic block types remain protected from conversion through the content editor.

### Compatibility

- No database migration, bundled module update or theme update is required.
- Existing blocks remain sanitized HTML unless their format is changed explicitly.
- Module API 1.0 remains unchanged.

## [0.1.4] - 2026-09-04

### Fixed

- General settings no longer raises a strict-Twig error when the validation error map is empty.
- Account security and email-change forms now apply the same defensive validation-key handling.
- General setting values have safe rendering defaults for incomplete legacy data.

### Compatibility

- No database migration, bundled module update or theme update is required.
- Module API 1.0 remains unchanged.

## [0.1.3] - 2026-09-04

### Fixed

- The account profile template now applies explicit defaults to every optional profile, validation and status value.
- Strict Twig rendering can no longer turn an empty profile map into a `display_name` runtime error.

### Compatibility

- No database migration, bundled module update or theme update is required.
- Module API 1.0 remains unchanged.

## [0.1.2] - 2026-09-03

### Fixed

- Account profile pages no longer raise a Twig runtime error when a legacy or incomplete account lacks its `user_profiles` row.
- Saving profile or avatar data safely recreates a missing profile while preserving normal updates.
- Public and account profile reads now apply safe locale, timezone, visibility and display-name defaults.

### Compatibility

- No database migration, bundled module update or theme update is required.
- Module API 1.0 remains unchanged.

## [0.1.1] - 2026-09-03

### Fixed

- Windows `CRLF` line endings in sanitized log messages now become one space instead of two.
- Consecutive mixed line endings are normalized without weakening credential and token redaction.

### Compatibility

- No database migration, bundled module update or theme update is required.
- Module API 1.0 remains unchanged.

## [0.1.0] - 2026-09-03

### Added

- Stable-release notes covering installation, safe upgrades, production validation and known operational limits.

### Changed

- NovaNuke graduates from rc.1 to its first stable release after the feature-freeze and production-readiness cycle.
- Documentation now identifies the supported module API 1.0 and the complete bundled CMS feature set as stable for the 0.1 series.

### Compatibility

- No database migration, bundled module update or theme update is required from 0.1.0-rc.1.
- PHP 8.3 remains the minimum supported runtime; PHP 8.4 is supported.
- Existing module API 1.0 integrations remain compatible.

## [0.1.0-rc.1] - 2026-09-03

### Added

- `production:check` preflight for PHP, extensions, HTTPS, environment, sessions, headers, application key and writable paths.
- Advisory production checks for PHP exposure, OPcache and SMTP readiness.
- Dedicated Bluehost/shared-hosting deployment and acceptance guide.
- Unit coverage for unsafe production configuration and shared-host PHP hardening.

### Security

- `public/.user.ini` disables displayed errors and PHP exposure while enabling strict cookie-only sessions.
- Apache denies direct access to hidden files inside the public document root.
- Release checks now require shared-host PHP hardening directives.

### Changed

- Production and release procedures now require `production:check` before public deployment.
- NovaNuke advances to its first release candidate with the feature set frozen.

## [0.1.0-beta.2] - 2026-09-03

### Performance

- Site settings now load in one query per request instead of one query per requested key.
- Module and theme table availability and inventories are memoized for the current request.
- Enabled menus, items and role restrictions now use three fixed queries instead of per-menu hydration.
- Block role restrictions are loaded in one batch rather than one query per rendered/admin block.

### Added

- Performance and shared-hosting guidance in `docs/PERFORMANCE.md`.
- MySQL integration coverage for repository cache invalidation and batched menu hydration.

### Security

- Every mutable request cache is invalidated after writes and is never shared between requests or users.
- Batched queries preserve the existing role filtering, schedule checks and page-visibility rules.

## [0.1.0-beta.1] - 2026-09-03

### Added

- Stable module API 1.0 identifier and explicit `api_version` declarations in bundled manifests.
- Compatibility rejection for unsupported module API major versions or newer minor revisions.
- Published stability policy covering module providers, context, migrations, routing, views, translations and events.
- Reflection tests guarding the frozen public module interface and context shape.

### Changed

- Enabled modules now complete a dependency-safe registration pass before any module begins booting.
- Optional cross-module service discovery no longer depends on database result order.
- NovaNuke enters feature freeze for the 0.1.0 beta stabilization cycle.

### Security

- Incompatible extension code is rejected before installation, update or activation.
- Registration failures prevent affected modules and unresolved dependents from booting and remain recorded in module diagnostics.

## [0.1.0-alpha.18] - 2026-09-03

### Added

- Central locale registry that securely discovers core JSON catalogues and their native names.
- Dynamic language choices shared by the installer, site settings and user profiles.
- `i18n:check` CLI audit for JSON validity, safe keys and English/Spanish catalogue parity.
- English and Spanish Media module catalogues and translated Media administration controls.
- Unit coverage for locale discovery, safe fallback and catalogue consistency.

### Changed

- Site and profile locale validation now uses the shared registry instead of repeated hard-coded lists.
- Media advances to 1.1.0 and requires NovaNuke 0.1.0-alpha.18.

### Security

- Locale filenames, native names, message keys and catalogue values are validated before use.
- Invalid configured or profile locales fall back to a known installed language without accepting paths.

## [0.1.0-alpha.17] - 2026-09-03

### Added

- Optional image-only Media module with an administrative gallery and reusable public paths.
- Strict JPEG, PNG and WebP validation using Fileinfo plus decoded image metadata, with size and dimension limits.
- Random server-generated filenames organized under `public/uploads/media/YYYY/MM/`.
- Optional Media selectors in the News and Pages editors.
- Extensible `media.usage.checking` hook that prevents deletion of referenced images.
- Unit coverage for image validation and usage aggregation, plus deployment and update documentation.

### Security

- Uploaded extensions, MIME types, file size and dimensions are verified on the server.
- Apache upload-directory rules disable indexes and executable handlers; no PHP can be uploaded through this module.
- All Media mutations require authentication, `media.manage`, CSRF validation and activity logging.

## [0.1.0-alpha.16] - 2026-09-03

### Added

- Optional SEO module serving extensible `/sitemap.xml` and `/robots.txt` endpoints.
- Validated `sitemap.collecting` contract with deduplication and a 50,000-URL ceiling.
- News and Pages sitemap providers restricted to currently published, publicly accessible content.
- Canonical, Open Graph and Twitter Card metadata for news articles and pages.
- News publication/modification metadata and `noindex,nofollow` for restricted pages.
- Unit coverage for sitemap validation, namespace correctness, ordering and content-template metadata.

### Security

- Sitemap base URLs accept only credential-free HTTP or HTTPS URLs configured by the administrator.
- Sitemap entries reject external, protocol-relative and query-string destinations.
- Private pages, drafts, future content and soft-deleted records are excluded from discovery.

## [0.1.0-alpha.15] - 2026-09-03

### Added

- Optional Notifications module with a private inbox, unread counter and individual or bulk read actions.
- Typed `private-message.sent` event and privacy-minimal notification integration for message recipients.
- Pending-comment notifications for active users holding the moderation permission.
- Module-manifest `events` declarations preserved and validated by the core contract.
- Ninety-day retention for read notifications through the existing maintenance hook.
- Spanish and English notification interface catalogues plus unit and MySQL integration coverage.

### Security

- Notification destinations accept internal paths only and never grant authorization by themselves.
- Per-user deduplication keys prevent obvious repeated event delivery.
- Private message bodies, email addresses, request data and secrets are excluded from notification payloads.

## [0.1.0-alpha.14] - 2026-09-03

### Added

- Portable `backup:files` TAR creation for modules, themes, public uploads, avatars and private downloads.
- Per-file SHA-256 and byte inventory in `NOVANUKE-BACKUP.json`, plus an archive digest in CLI output.
- Downloads orphan inspection with explicit `--delete` cleanup and a 24-hour grace period.
- Unit coverage for archive contents, manifest integrity, symlink exclusion, dry runs and deletion safeguards.

### Security

- File backups remain below `storage/private`, use random names, restrictive permissions and atomic finalization.
- Backup traversal never follows symbolic links and never includes `.env`, runtime logs, sessions, caches or existing backups.
- Download cleanup recognizes only server-generated filenames and preserves referenced, recent and unexpected files.

## [0.1.0-alpha.13] - 2026-09-03

### Added

- Explicitly isolated MySQL/MariaDB integration-test suite for local development.
- End-to-end service coverage for login, session identity, login history, authorization and authentication events.
- Password-reset integration coverage for hashed single-use tokens, password replacement and session-version invalidation.
- Module lifecycle coverage for installation, permission registration, activation, route boot and both uninstall choices.
- Dedicated `.env.testing.example`, cross-platform Composer runner and complete Laragon testing documentation.
- CSRF tests covering token validity, rotation and session isolation.

### Changed

- `composer test` now runs the fast unit suite; `composer test:integration` explicitly opts into temporary database creation.
- `composer test:all` runs both suites in the required order.

### Security

- Integration databases use an unconfigurable `novanuke_test_` prefix plus 16 random hexadecimal characters.
- Cleanup rejects names outside that exact pattern and never uses the normal NovaNuke database as a deletion target.
- Failed migration setup attempts remove the newly created temporary database before propagating the failure.

## [0.1.0-alpha.12] - 2026-09-03

### Added

- Read-only `migrate:status` command covering core and installed-module migrations.
- Detection of pending migrations, executed migrations missing from disk and copied module updates not yet applied.
- Migration status and production warnings in `/admin/system`.
- Deterministic migration-file comparison tests and update workflow documentation.

### Changed

- Update instructions now inspect database state both before core migration and after controlled module updates.
- `migrate` is explicitly documented as a core-only operation; module migrations remain owned by the module manager.

### Security

- Missing historical migration files now produce a visible warning instead of silently appearing healthy.
- Status inspection performs no schema changes and exposes migration identifiers without database credentials or paths.

## [0.1.0-alpha.11] - 2026-09-03

### Added

- Central administration access gate covering `/admin` and every nested route.
- `security:audit` CLI command for authorization checks outside the web panel.
- Detection of roles that hold administrative capabilities without `admin.access`.
- Unit coverage for exact administrative namespace matching and guest/member responses.

### Changed

- Administrative requests now require both `admin.access` and the controller's operation-specific permission.
- The core permission audit now includes `menus.manage`.

### Security

- Directly entering a module administration URL can no longer bypass the central panel-access permission.
- Similar public paths such as `/administrator` and `/news/admin` are not accidentally captured by the gate.

## [0.1.0-alpha.10] - 2026-09-03

### Added

- Transactional `maintenance:prune` command with a non-destructive `--dry-run` mode.
- Fixed retention periods for expired security records, access history and administrative activity.
- Typed `maintenance.pruning` extension hook so enabled modules can own their data-retention rules.
- Search-query and privacy-preserving statistics retention, plus Laragon and shared-hosting scheduling guidance.

### Changed

- CLI requests are excluded from public statistics collection.
- Search and Statistics manifests now declare their maintenance integration and require this core release.

### Security

- Expired authentication tokens and rate-limit records can now be removed routinely without exposing token values.
- Live pruning runs in one database transaction and rolls back when a core or module operation fails.

## [0.1.0-alpha.9] - 2026-09-03

### Added

- Typed `user.registered`, `user.email_verified` and `user.logged_in` core events.
- Privacy-minimal immutable payloads containing only user IDs and required registration state.
- Complete authentication-event contract documentation and payload tests.

### Changed

- Registration, verification and login dispatch module notifications only after their core operations succeed.
- Authentication listener failures are logged without breaking or reversing the completed user action.

## [0.1.0-alpha.8] - 2026-09-02

### Added

- Public `/resend-verification` recovery for pending accounts with expired or lost registration links.
- Neutral success responses that do not disclose whether an account exists.
- Replacement of every older verification token with one new 24-hour hashed token.
- Recovery links from login, registration completion and invalid verification screens.
- English and Spanish labels, recovery documentation and validation tests.

### Security

- Resend requests require CSRF and are rate limited by normalized email plus IP address.
- Verification recovery remains available during maintenance and after public registration is closed.

## [0.1.0-alpha.7] - 2026-09-02

### Added

- Password-confirmed email changes from `/account/email`.
- Hashed one-time confirmation tokens delivered only to the proposed address and expiring after 60 minutes.
- Dedicated development-log and SMTP email-change messages.
- `user.email_changed` hook and privacy-conscious security activity entries.
- English and Spanish account-email interface, documentation and validation tests.

### Security

- The original email remains active until confirmation and database uniqueness is checked again atomically.
- Successful confirmation marks the address verified and invalidates every existing authenticated session.
- Email-change requests are protected by CSRF and limited to three per account per hour.

## [0.1.0-alpha.6] - 2026-09-02

### Added

- Authenticated account-security page with the 20 most recent successful sign-ins.
- Conservative browser/platform summaries without exposing raw user-agent strings.
- Password- and username-confirmed account anonymization with CSRF and rate limiting.
- `user.anonymized` hook for module-owned personal-data cleanup.
- Account lifecycle documentation and unit tests for confirmation and device labels.

### Security

- Anonymization removes credentials, tokens, roles, profile data, avatar, login history and recorded activity IP addresses.
- Authored content remains intact under an anonymous identity and the final active Super Administrator is protected.

## [0.1.0-alpha.5] - 2026-09-02

### Added

- Public member pages at `/users/{username}` with public or members-only visibility.
- Authenticated profile editor with display name, plain-text biography, locale and timezone preferences.
- Private avatar storage with verified JPEG, PNG or WebP content, strict dimensions and controlled delivery.
- Authenticated password changes with current-password verification, rate limiting and session invalidation.
- Profile, avatar storage and validation tests plus English and Spanish account labels.

### Changed

- Signed-in users now receive their personal locale and timezone without changing site-wide defaults.
- Default and Classic Portal themes 1.6.0 expose the account editor from their headers.

## [0.1.0-alpha.4] - 2026-09-02

### Added

- Lightweight JSON catalogue translator with locale fallback and safe scalar placeholders.
- Automatic isolated translation namespaces for enabled modules and the active theme.
- Twig `trans()` function with output escaping retained by default.
- Initial English and Spanish catalogues for public home, login and registration screens.
- English and Spanish language packs in the bundled themes and Welcome module.

### Changed

- Default and Classic Portal themes 1.5.0 render structural interface text through translations.
- Welcome 1.1.0 demonstrates the module language-pack contract.

## [0.1.0-alpha.3] - 2026-09-02

### Added

- Protected general settings panel with strict validation, CSRF, authorization and activity auditing.
- Configurable site identity, administrator email, locale, timezone, date format and shared pagination size.
- Configurable welcome, News, Pages or Downloads homepage with enabled-module checks.
- Public metadata, language and date rendering driven by installed settings.

### Changed

- Maintenance mode now belongs to General settings; System information remains read-only diagnostics.
- Registration and password recovery links use the installed public site URL.
- News, Downloads, Web Links and Search consume the shared pagination setting.
- Bundled content modules and reference themes carry patch-version updates for the new setting globals.

## [0.1.0-alpha.2] - 2026-09-02

### Added

- Permission-aware administrative dashboard metrics and navigation.
- Recent users, content and administrative activity summaries.
- Pending-comment counts, optional-module awareness and module health totals.
- Production configuration warnings linked to protected system diagnostics.

## [0.1.0-alpha.1] - 2026-09-02

### Added

- Initial PHP 8.3 and Composer project definition.
- Environment and application configuration.
- Lightweight service container.
- Request, response, router and HTTP kernel.
- Centralized production-safe error handling.
- PDO connection factory and migration runner.
- Twig view rendering and starter page.
- Apache front controller configuration.
- Initial PHPUnit test suite.
- Safe cache status and clearing commands restricted to `storage/cache`, including optional OPcache reset.
- Authenticated SMTP mail transport with encrypted SSL/TLS and STARTTLS options.
- HTML and plain-text password recovery and email verification messages.
- SMTP readiness diagnostics and Bluehost-oriented production documentation.
- Central release version shared by the core, installer, installation lock, CLI and public UI.
- Distribution safety audit through `php bin/cms release:check`.
- Atomic, exclusive and versioned installation lock creation.
- Production and debug error-log redaction for credentials, bearer tokens and token-like hashes.
- Alpha installation, updating, recovery and release-verification documentation.
- Web installer with server requirement checks.
- CSRF token and hardened session foundations.
- Atomic environment configuration writer.
- Initial user, profile, role, permission and settings tables.
- Initial Super Administrator and six built-in roles.
- Installation lock that removes installer routes after setup.
- Installer validation and environment writer tests.
- Username/email login and POST-only logout.
- Authenticated session manager with session ID regeneration.
- Protected Super Administrator dashboard.
- Suspended and deleted account enforcement.
- Login attempt throttling and generic credential errors.
- Login history migration and last-access recording.
- `composer migrate` command for installed sites.
- Login validation and safe redirect tests.
- Password recovery request and reset screens.
- Hashed, expiring, single-use password reset tokens.
- Development log mailer with a production safety lock.
- Authentication versioning to invalidate prior sessions after password changes.
- Generic recovery responses that do not reveal registered email addresses.
- Password reset request throttling.
- Reset token, password policy and log mailer tests.
- Immediate validation screen for consumed or expired password reset links.
- Public registration, closed by default.
- Configurable email verification and 24-hour one-time verification tokens.
- Automatic Member role assignment for public registrations.
- Super Administrator user-registration settings screen.
- Registration validation and verification mail tests.
- Server-enforced role and permission authorization service.
- Sixteen initial core and module permission definitions.
- User role assignment, suspension and reactivation screens.
- Role permission management screen.
- Protection for the final active Super Administrator.
- Persistent database-backed rate limits for login, recovery and registration.
- Administrative activity log and viewer.
- Authorization developer documentation and role-safety tests.
- Module manifests, detection and compatibility checks.
- Module installation, activation, deactivation, updating and controlled uninstallation.
- Module-owned migration history and dependency protection.
- Synchronous prioritized event dispatcher for hooks.
- Namespaced Twig view paths for modules.
- Administrative modules panel and audit events.
- Official Welcome lifecycle demonstration module.
- Module development documentation and manifest/event tests.
- Theme manifests, detection, compatibility and lifecycle management.
- Active theme selection and validated appearance settings.
- Safe publishing of theme CSS, JavaScript, images and fonts.
- Twig layout, partial and core template overrides.
- Theme-level module template overrides.
- Nova Default and Classic Portal reference themes.
- Theme development documentation and manifest/asset security tests.
- Configurable block positions, schedules, page rules and role visibility.
- Sanitized enriched HTML blocks with no executable administrator code.
- Administrative block editor and block activity events.
- Multiple menus with hierarchical items, ordering and role visibility.
- Validated internal, module and HTTP/HTTPS external menu destinations.
- Theme-overridable recursive menu rendering and seeded primary navigation.
- Installable News module with editorial permissions and public friendly URLs.
- Draft, scheduled and published news workflow with server-side authorization.
- News categories, topics, tags, featured entries, basic SEO and session-limited view counting.
- Extensible module entries through the `admin.menu.building` hook.
- Reusable Comments module with polymorphic content targets and module-owned migrations.
- Threaded replies, configurable moderation, guest policy and a 15-minute author edit window.
- Comment abuse reports, duplicate-report protection and persistent submission rate limits.
- News 1.1.0 optional Comments integration through `comments.content.checking`.
- Comment provider and security documentation plus hierarchy/contract tests.
- Installable Pages module with drafts, scheduled publication and friendly URLs.
- Page hierarchy with cycle prevention, public/member/role access and safe enriched content.
- Default and landing page templates with theme override support and basic SEO metadata.
- Optional page comments with publication and viewer-access validation.
- Page directory, internal-menu guidance and Pages developer documentation.
- Extensible `page.rendering` hook with post-listener template validation.
- News 1.2.0 RSS 2.0 feed containing the latest 20 publicly available articles.
- DOM-based XML generation, absolute feed URLs, RSS dates and safe locale normalization.
- RSS response type, MIME-sniffing protection and five-minute public cache metadata.
- RSS autodiscovery and visible feed links in Nova Default and Classic Portal 1.4.0.
- Installed site name, URL and locale exposed consistently to public Twig templates.
- Downloads 1.0.0 module with local private files and tracked external sources.
- Hierarchical download categories, publication workflow, featured items and role-aware access.
- Server-validated upload extension, MIME, size, filename generation and real-path containment.
- Streamed attachment responses and safe HTTP/HTTPS external redirects.
- Newest, popular and alphabetical catalog ordering plus MySQL-backed search.
- Twenty-four-hour duplicate counter suppression without raw visitor identity storage.
- CSRF-protected, deduplicated and rate-limited broken-download reports.
- `download.completed` extension event and Downloads security documentation.
- Search 1.0.0 with a provider registry exposed through `search.providers.registering`.
- Unified newest-first search across News 1.3.0, Pages 1.1.0 and Downloads 1.1.0.
- Content-type filtering, global pagination and safely escaped result highlighting.
- Publication and viewer-role enforcement inside every bundled search provider.
- Optional privacy-preserving popular-term counts, disabled by default.
- Search provider documentation and registry, aggregation and XSS-focused tests.
- Private Messages 1.0.0 with inbox, sent history, unread state and two-user conversations.
- Participant-specific conversation removal without deleting the other user's history.
- Bidirectional message blocking, abuse reports and a permission-protected moderation queue.
- Persistent per-account send and report limits plus CSRF-protected message actions.
- Plain-text message validation and Private Messages security documentation.
- Polls 1.0.0 with draft, active and closed states plus optional UTC scheduling.
- Single and multiple-choice voting with immutable options after voting begins.
- HMAC-based authenticated and guest duplicate-vote controls without raw voter network data.
- Public results, administrative poll management and a reusable active-poll block.
- Generic `block.rendering` hook for trusted module-provided block content.
- Preservation of module block type and configuration when layout settings are edited.
- Web Links 1.0.0 with categories, featured entries and moderated user submissions.
- Search and newest, popular or alphabetical ordering for the public link directory.
- Strict HTTP/HTTPS validation and controller-mediated external redirects.
- Twenty-four-hour duplicate visit suppression without storing raw visitor identities.
- Deduplicated, rate-limited broken-link reports and an administrative review queue.
- Statistics 1.0.0 with daily aggregate traffic and no individual browsing histories.
- Broad section, referrer-host, browser-family and device-family summaries.
- Administrative totals, recent activity and most-viewed content across optional modules.
- Independently configurable collection and public-statistics settings.
- Disabled-by-default dynamic statistics summary block and privacy documentation.
- Request referrer validation with private/authentication routes excluded from tracking.

### Changed

- NovaNuke now routes unconfigured installations exclusively to the installer.
- Migration recording accounts for MySQL implicit DDL commits.
- Administrative CSRF tokens remain stable during a signed-in session, preventing stale forms across tabs.
- Theme actions use a POST/Redirect/GET response to prevent accidental form resubmission.
- Classic Portal blocks retain their panel styling while updated theme assets are being republished.

### Security

- Application-level CSP, MIME-sniffing, framing, referrer and browser-capability headers across successful and error responses.
- Explicit opt-in HSTS restricted to production HTTPS configurations.
- Permission-protected system diagnostics without credentials, tokens, private paths or detailed server errors.
- Atomic CLI database backups stored outside the public tree with restrictive permissions and unpredictable names.
- Production deployment, shared-hosting, backup/restore and security-checklist documentation.
- CSRF-protected maintenance controls with 503 responses, recovery access and Super Administrator preview.
- Authorization auditing for active super administrators, required core permissions and unsafe public-role grants.
