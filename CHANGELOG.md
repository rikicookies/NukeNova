# Changelog

All notable NovaNuke changes will be documented here.

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
