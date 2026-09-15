## 0.4.0-rc.4 - unreleased

- Extended progressive AJAX actions to notifications, friends, and theme lifecycle/configuration while preserving viewport position.

- Added progressive AJAX enhancement for comment reactions, module lifecycle/audience actions, and poll voting while preserving normal POST/redirect fallbacks.

### Fresh-install defaults and public module navigation
- Fresh installs provision the recommended bundled modules enabled, activate NovaModern, create the dynamic Modules block, and expose POST/CSRF logout for authenticated users across bundled themes.
- Added Polls to manifest-driven public module navigation so an enabled Polls module appears automatically in the Modules block and disappears when disabled.
- Demo Content remains excluded from automatic fresh-install provisioning.

### Release reliability and recovery
- Made scheduled-membership activation and expiration events retryable: lifecycle markers are committed only after listeners succeed under a row lock.
- Added a durable `running` / `dirty` / `completed` operation ledger for Core and module migrations without replacing existing history.
- Blocked ordinary Core/module migration and module-uninstall runners whenever recovery is required; interrupted work must now be reconciled explicitly before any later schema operation.
- Added checksum-guarded recovery, verified postconditions, repeat-safe bundled migrations, and a database-scoped MySQL/MariaDB advisory lock.
- Added `migrate:recover` for interrupted Core, module install/update, and module uninstall operations.
- Added MySQL fault-injection coverage for DDL committed before migration history, partial DDL, module lifecycle recovery, legacy history, and concurrent runners.
- Hardened database backups with `REPEATABLE READ` / `START TRANSACTION WITH CONSISTENT SNAPSHOT` for InnoDB tables and explicit snapshot metadata.
- Added durable backup-set IDs shared by SQL and TAR artifacts; release verification no longer treats nearby mtimes as proof of pairing.
- Added `backup:create` for coordinated DB + files backup sets while retaining standalone backup commands.
- Added disposable SQL import verification against an operator-provisioned empty MySQL database; release deployment remains failed/NOT VERIFIED when restore evidence is unavailable.
- Added corruption/import/recovery regression coverage for NN-BACKUP-01.
- Separated mail configuration validity from production readiness and delivery verification for NN-MAIL-01.
- Production readiness now requires structurally valid SMTP; `MAIL_MAILER=log` remains supported only for development/test configuration checks.
- Added durable per-workflow mail acceptance for registration verification, password reset, and email change; RC deployment remains NOT VERIFIED until all three real delivery workflows are recorded for the current SMTP/site configuration.

## 0.4.0-beta.15 - 2026-09-10

## 0.4.0-rc.3 - 2026-09-12

### RC.3 checkpoint regression fixes
- Corrected the isolated installer bootstrap test to use the real `Response::content()` API and restore NovaNuke's temporary error handler after each test.
- Removed bootstrap-only `NOVANUKE_ROOT` dependencies from mail configuration and installer routes so isolated uninstalled-site tests work in an already-installed development tree.
- Fresh-install bootstrap tests now use an isolated temporary distribution root instead of assuming the developer checkout has no `.env` or installation lock.
- Updated the public-menu ordering contract to the final shared admin-style controls.
- RC source-check fixtures now include the required `composer.lock`.


### NovaModern mobile hardening
- Fixed the NovaModern mobile administration sidebar so long navigation owns its viewport scroll, honors dynamic viewport height and iPhone safe-area padding, and no longer strands lower menu items off-screen.
- Locks page scrolling while the mobile sidebar is open and keeps the active navigation item visible.
- Hardened administration tables for narrow screens with contained horizontal scrolling and mobile-safe empty states.
- Normalized narrow-screen module headings, forms, split layouts and action rows without changing module behavior.
- Added regression coverage for mobile sidebar scrolling and responsive admin-table behavior.

### Administration navigation ordering
- Added persistent administration-sidebar ordering under Admin -> Menus.
- Administrators with `menus.manage` can reorder navigation groups and links; the order is stored in settings and applied site-wide while permissions continue to filter visibility.
- Desktop supports drag ordering; touch devices have explicit up/down controls so ordering does not depend on HTML5 drag support.
- No database migration is required.

### Public menu ordering
- Primary navigation ordering now uses the same visual card language, spacing and move controls as the administration navigation sorter.
- Primary navigation sorter controls are now explicitly visible for every item (drag handle plus up/down buttons), including narrow mobile layouts.
- Dedicated the visual public-menu sorter to the `primary` menu used by NovaModern's main public sidebar, so the main navigation can be reordered as directly as the administration sidebar.
- Added the same visual ordering workflow to normal/public menus in Admin -> Menus.
- Each menu can be reordered by drag on desktop or explicit up/down controls on touch devices.
- Saving updates only `sort_order`; parent/child relationships, role visibility, enabled state and destinations remain unchanged.
- No migration is required.

### Release hardening
- Example-secret release validation now accepts both unquoted blank values and quoted-empty values while still rejecting real credential values.
- No content schema changes and no new module migrations.


## 0.4.0-rc.2 - 2026-09-12

### Shared-hosting installer bootstrap correction
- Fixed a release-blocking fresh-install failure discovered on Bluehost: an uninstalled web request no longer resolves PDO-backed maintenance/auth/module services before database credentials are submitted.
- Installer-mode Kernel dispatch is now explicitly database-independent until `storage/installed.lock` exists.
- Added regression coverage proving `/install` renders and `/` redirects to `/install` without a configured database.
- Added a static installer-safety contract to prevent DB-backed Kernel guards from being eagerly resolved on uninstalled sites.
- Release packages now require `composer.lock` so RC/stable deployments install the exact dependency graph tested during acceptance.
- Carries the Beta 28 pre-RC fixes for pre-created empty databases, older-upgrade runtime-directory provisioning, and third-party/local modules.

### Acceptance target
- Replace RC.1 source with RC.2 on the Bluehost test site without manually creating `.env`.
- Confirm `/install` renders before any database credentials are configured.
- Install into the already-created empty Bluehost database.
- Run installed-site and release/deployment checks after installation.


## 0.4.0-rc.1 - 2026-09-11

### First Release Candidate
- Feature freeze begins for the 0.4.0 line.
- Carries the complete Beta 28 visual-consistency pass across all bundled modules.
- Carries pre-RC compatibility fixes for shared-hosting/pre-created empty databases, runtime-directory provisioning during older upgrades, and third-party/local module tolerance in bundled-module audits.
- Upgrade support includes Beta 28 as a direct source release.
- RC acceptance is now focused on packaged fresh installation, older-site upgrade, backup/restore, production configuration, SMTP, permissions and shared-hosting behavior.
- No database migration is introduced by RC.1.

### Acceptance target
- Fresh install from the packaged archive into a pre-created empty database.
- Existing-site upgrade from Beta 28.
- Production-like install on Bluehost with HTTPS, SMTP, Linux permissions and `composer check:release` / `php bin/cms rc:deployment`.


## 0.4.0-beta.28 - 2026-09-11

### Final beta — bundled-module visual consistency pass
- Completed an explicit visual audit of every bundled module before RC.1.
- Fully refreshed Polls public/admin presentation: page hierarchy, cards, voting/results surfaces, status badges, inventory actions and empty states.
- Fully refreshed Statistics public/admin presentation: metric cards, privacy context, breakdown panels, activity tables and empty states.
- Refreshed Friends, Notifications, Quotes, Private Messages, Search, Media, Comments, Demo Content and Welcome using shared page/header/card/form/action primitives.
- Brought the WebLinks community-submission screen into the current page/header/form system.
- Re-reviewed previously modernized Downloads, News, Pages, WebLinks and Wiki surfaces instead of assuming prior work was sufficient.
- Preserved Wiki's specialized Markdown/directory/map presentation while aligning empty states.
- Added a shared bundled-module presentation layer in `public/assets/css/app.css`, with explicit NovaModern and Classic light-palette overrides.
- Added `BundledModuleVisualConsistencyTest` plus `docs/VISUAL_AUDIT_BETA28.md`; the test requires every bundled module to appear in the visual-audit inventory.
- Added consistent responsive module sections, metric grids, card lists, action rows, form surfaces and reusable empty-state treatment across old/new module screens.

### Documentation contract correction
- Updated the RC documentation contract test to assert the Beta 28 pre-RC wording instead of the superseded `Release Candidate preparation` phrase.
- Corrected the RC documentation contract so it validates the restore workflow that Beta 28 actually documents, without requiring a nonexistent `backup:restore-check` command.

### Pre-RC compatibility corrections
- Installer now tries to use a pre-provisioned database before attempting `CREATE DATABASE`, allowing empty databases created by Laragon/control panels and shared-hosting accounts without database-creation privileges.
- Existing non-empty databases remain protected by the empty-schema guard.
- `upgrade:complete` now provisions the complete current runtime-directory contract before recording upgrade success, including directories added after older Alpha/Beta releases such as `storage/private/avatars`.
- The bundled-module visual audit now requires every official bundled module while allowing additional third-party/local modules such as `TestModule`.
- Added regression coverage for pre-existing empty database installs and the updated upgrade/module contracts.

### Scope
- No database migration.
- No new module or major feature.
- Existing routes, permissions and content behavior are preserved.
- Beta 28 is intended to be the final beta before RC.1 acceptance.


## 0.4.0-beta.27 - 2026-09-11

### RC acceptance batch — backup recovery and deployment aggregation
- Fixed the `rc:deployment` PaymentHealthCheck namespace regression (`Core\Billing`, not the nonexistent `Core\Payments`).
- Added `BackupRecoveryCheck` to verify the latest database/file backup pair and perform a real disposable extraction of the verified file archive.
- Added `backup:restore-check` CLI command; it never overwrites the active site and removes its temporary restore directory afterward.
- Added backup freshness validation for RC deployment acceptance (latest matched pair must be no older than 24 hours).
- Expanded `rc:deployment` so it now includes installed-site health and backup recovery in addition to production, authorization, Membership, Payments, mail, bundled themes and deployment-secret policy.
- Added regression tests for the aggregate RC deployment contract and disposable backup recovery path.
- Updated RC/recovery documentation to require actual extraction evidence rather than `backup:verify` alone.

### Scope
- No database migration.
- No user-facing feature work.
- No deferred visual/module polish work.


## 0.4.0-beta.26 - 2026-09-11

### RC security and request-boundary checkpoint
- Fixed the Beta 25 external-redirect regression: credential-bearing URLs and non-default explicit ports are now rejected; explicit HTTP 80 / HTTPS 443 remain valid.
- Hardened CSRF validation so malformed tokens fail without creating or rotating session state as a side effect.
- Hardened database rate-limit configuration and key validation, and replaced unconditional expired-row cleanup on every hit with bounded probabilistic cleanup.
- Carries forward the accumulated Beta 25 RC hardening: session cookie scope / `__Host-` invariants, production HTTPS CSP upgrade policy, deployment-secret validation, consolidated `rc:deployment`, request-path canonicalization, ZIP signature checks, image pixel ceilings, and the file-restore CLI import regression fix.
- Added regression/contract coverage for the above boundaries.

### Scope
- No database migration.
- No visual/module polish.
- Intended as the next accumulated checkpoint after Beta 25, not a one-fix release.

## 0.4.0-beta.25 - 2026-09-11

### RC hardening batch — sessions, HTTP boundaries, uploads and deployment preflight
- Added configurable `SESSION_PATH`/`SESSION_DOMAIN` and enforced the PHP `__Host-` cookie invariants: Secure, root path and no Domain.
- Expanded production readiness with absolute session lifetime, cookie scope and HSTS-policy checks.
- Production HTTPS CSP now includes `upgrade-insecure-requests`.
- Added consolidated `rc:deployment` preflight covering production, authorization, memberships, payments, mail, bundled themes and deployment-secret policy.
- Added deployment-secret checks for APP_KEY entropy, production database credentials and SMTP credentials without exposing secret values.
- Hardened request path canonicalization: dot segments normalize before routing and encoded slash/backslash/null-byte ambiguity is rejected.
- Hardened external redirects by rejecting embedded credentials and unexpected ports.
- Hardened ZIP uploads with archive-signature verification and image uploads with a total pixel ceiling.
- Fixed the Beta 23 file-restore CLI import regression and added contract coverage so the restore command cannot silently lose its implementation import.
- Expanded RC/security/production documentation and release-check aggregation.

### Scope
- No database migration.
- No visual/module polish work.
- No new end-user feature.


## 0.4.0-beta.24 - 2026-09-11

### RC preparation — mail, themes, authorization and regression batch
- Added database-independent `theme:check` to validate bundled theme manifests, compatibility, declared layout templates, screenshots and asset directories.
- Added `mail:check` to validate log/SMTP transport configuration without opening a delivery connection or exposing SMTP credentials.
- Added mail configuration unit coverage for development log mail, valid SMTP and invalid-secret-safe SMTP diagnostics.
- Strengthened `security:audit` so the Super Administrator role must retain every required Core permission.
- Added MySQL integration coverage proving the authorization audit detects a missing Super Administrator permission.
- Added MySQL theme lifecycle integration coverage for bundled theme install, activation, switching, active-theme uninstall protection and uninstall of an inactive theme.
- Added bundled-theme source-package validation to `check:rc-source`.
- Added mail configuration validation to `check:site` and both theme/mail checks to `check:release`.
- Updated RC acceptance and production documentation for structural SMTP validation and bundled-theme preflight.

### Scope
- No database migration.
- No visual redesign or deferred module-polish work.
- No SMTP message is sent by `mail:check`; real delivery remains an RC acceptance step.


## 0.4.0-beta.23 - 2026-09-11

### RC preparation — verified private-file restore
- Added `FileBackupRestorer` for verified extraction of NovaNuke TAR backups into an empty destination.
- Restore verifies the existing backup manifest/checksums before creating the destination and refuses non-empty targets.
- Added `backup:restore-files --archive=PATH --destination=PATH`; there is intentionally no overwrite or force mode.
- Added regression coverage for successful restore, tampered archives and non-empty destination refusal.
- Added recovery documentation and RC acceptance guidance separating safe private-file restore from explicit operator-controlled SQL database restore.

### Scope
- No database migration.
- No live/in-place restore behavior.
- No module/theme visual work.


## 0.4.0-beta.22 - 2026-09-11

### RC preparation — automated fresh-installer acceptance
- Added MySQL integration coverage that runs the real `InstallerService` against a random empty database and temporary application root.
- Fresh-installer integration verifies current Core version recording, installation lock creation, generated environment configuration, Super Administrator + role assignment, complete Core migration execution, and required runtime storage provisioning.
- Added a destructive-safety regression proving the installer refuses a non-empty database without deleting pre-existing tables or writing `.env` / installation lock state.
- Updated RC and clean-install acceptance documentation to distinguish automated installer regression from the required browser fresh-install pass.

### Scope
- No runtime feature change.
- No database migration.
- No module/theme visual work.
- Beta 22 remains a pre-RC acceptance checkpoint.


## 0.4.0-beta.21 - 2026-09-11

### RC fixture completeness correction
- Completed the isolated RC test fixture with the structural directories required by `ReleaseChecklist`.
- Added `app/` so public-root isolation can be evaluated correctly.
- Added `storage/sessions/` so required distribution structure passes in the synthetic clean fixture.
- Keeps RC cleanliness tests isolated from installed-site runtime state.

### Scope
- Test-only correction plus release metadata.
- No database migration or runtime feature change.


## 0.4.0-beta.20 - 2026-09-11

### RC test isolation correction
- Corrected `ReleaseCandidateChecklistTest` so PHPUnit no longer assumes the active checkout is an untouched release archive.
- RC cleanliness behavior is now tested against an isolated temporary distribution fixture.
- Added explicit fixture coverage proving `.env`, installation locks, runtime logs/cache and backups are rejected by `rc:check`.
- Existing installed sites can now run `composer test:checkpoint` without failing merely because legitimate runtime state exists.
- `composer check:rc-source` remains intentionally restricted to a freshly extracted clean source package.

### Scope
- No runtime feature change.
- No database migration.
- No production data cleanup.
- No change to RC cleanliness policy; only test isolation was corrected.


## 0.4.0-beta.19 - 2026-09-11

### Release Candidate preparation — source/package acceptance
- Added database-independent `php bin/cms rc:check` for clean source-package acceptance.
- Added `composer check:rc-source` combining RC cleanliness, release checklist and distribution smoke checks.
- RC source validation rejects `.env`, `storage/installed.lock`, runtime logs/cache/backups, stale release metadata and incomplete RC documentation.
- Added `docs/RC_ACCEPTANCE.md` covering source package, fresh install, real upgrade, backup/restore, security, SMTP, production configuration, bundled-module/theme regression and acceptance recording.
- Updated stale README, clean-install and update instructions from older alpha/beta wording to the current lifecycle-aware workflow.
- Reworked production release procedure around backups, upgrade preflight/completion, `check:site`, security audit and `check:release`.
- Added regression contracts for RC documentation and separation between source-package checks and installed-site checks.

### Scope
- Feature freeze remains in effect.
- No database migration.
- No Membership/Payment behavior change.
- No deferred module visual-polish work.
- Beta 19 remains a pre-RC checkpoint; tagging RC still requires the acceptance matrix.


## 0.4.0-beta.18 - 2026-09-10

### Release-candidate readiness — lifecycle-aware validation
- Added read-only `php bin/cms site:check` for an already-installed NovaNuke site.
- Installed-site health now validates `.env`, installation lock, recorded Core version, migration state, installed module versions, and all required runtime directories.
- Split Composer lifecycle checks into `check:install`, `check:site`, and `check:release`.
- `check:install` is now explicitly pre-install only and may correctly require `.env` / installation lock to be absent.
- `check:site` is the development/staging health pass for an existing installation and includes Membership + optional Payment integrity.
- `check:release` now combines distribution checklist/smoke, installed-site health, production readiness, Membership integrity, and Payment integrity without running installer-only checks.
- Added regression contracts so installer absence rules cannot leak back into installed-site/release validation.
- Documented the distinction between install, site and production-release validation.

### Scope
- No feature or database-schema change.
- No module visual-polish work in this checkpoint.
- Beta 18 is a pre-RC validation/hardening checkpoint, not the Release Candidate itself.


## 0.4.0-beta.17 - 2026-09-10

### Beta 3 — Optional payments stabilization checkpoint
- Hardened receipt idempotency for concurrent duplicate provider delivery by mapping MySQL duplicate-key races to a domain-level duplicate receipt and reloading the canonical receipt.
- Added read-only `php bin/cms payment:check` for receipt schema, ownership, known plan keys, provider-reference uniqueness and registered provider visibility.
- Added payment integrity to `composer check:release`.
- Added integration coverage for duplicate-key mapping and normal payment health state.
- Added contract coverage for concurrent duplicate handling and payment health checks.

### Scope
- Payments remain optional and disabled by default.
- No bundled payment provider, checkout route, webhook route, recurring billing, refunds or automatic cancellations.
- No card number, CVV/CVC, bank credential or provider secret storage.
- No new database migration beyond Beta 16's `payment_receipts` table.


## 0.4.0-beta.16 - 2026-09-10

### Beta 3 — Optional payments foundation
- Added provider-neutral `PaymentProviderInterface`, `PaymentProviderRegistry` and normalized `VerifiedPayment` trust boundary.
- Added `MembershipProvisionerInterface` so verified external grants reuse Membership rules/events instead of writing entitlement rows directly.
- Added durable `payment_receipts` idempotency storage keyed by provider + external reference.
- Added transactional `MembershipPaymentProvisioner`: duplicate provider delivery is a no-op; conflicting reuse of a payment reference is rejected; failed Membership provisioning rolls the receipt back.
- Entitlement replacement now cooperates safely with an existing outer transaction and accepts a nullable system/external granter.
- Added focused unit/architecture coverage and MySQL integration coverage for verified payment fulfillment, duplicate delivery, tamper detection, provider mismatch and rollback.
- Added `docs/PAYMENTS.md` documenting the provider boundary, sensitive-data rules and provider-module integration.

### Scope
- Payments remain optional and disabled by default.
- No Stripe, PayPal or other payment provider is bundled.
- No checkout or webhook route is exposed by Core.
- No card number, CVV/CVC, bank credential or provider secret is stored in `payment_receipts`.
- Manual Membership administration remains unchanged.

### Database
- Adds `2026_09_10_000022_create_payment_receipts.php`.


### Checkpoint contract cleanup
- Updated the admin dashboard membership-route contract to the dedicated Memberships admin introduced in Beta 2.
- Tightened the state-changing GET-route contract so benign read-only routes such as `/account/deleted` are not false positives.
- Updated the upgrade CLI source-chain contract for Beta 14 -> Beta 15.
- No runtime Membership behavior or database schema change in this checkpoint.

# Changelog

## NN-RC-HARDENING-01

- Hardened the clean-source gate to reject sessions, uploads, private user files, environment variants and root-level SQL/TAR/ZIP/backup artifacts.

## NN-BACKUP-01

- Added manifest-backed, atomically published SQL + files backup sets with durable IDs, sizes, SHA-256 hashes and portable runtime metadata.
- Verification now treats the external manifest as authoritative and rejects incomplete, mismatched, missing or corrupted components before restore work.
- Added test-only backup phase fault injection and MySQL integration coverage for completed and interrupted set creation.

## 0.4.0-rc.4 — Batch 4 reliability checkpoint

- Fixed NN-THEME-01: theme asset publication now validates the complete source before touching active assets, copies to a sibling staging tree, verifies SHA-256 content, and swaps by rename with rollback.
- Added runtime fault-injection coverage for mid-publication copy failure and swap failure; previous active assets remain available and temporary trees are cleaned.
- No database migration, bundled theme version change, visual redesign, or unrelated refactor is included in this batch.


## 0.4.0-beta.14 - 2026-09-10

### Checkpoint test-suite stabilization
- Fixed PHP-variable interpolation warnings in CLI and installer contract tests.
- Updated stale Beta 1 migration contract so later Beta 2 membership migrations are valid.
- Made the VIP constant contract formatting-insensitive.
- Updated Wiki optional-comments contract to the Core event/interface architecture.
- Repaired the inter-module dependency regex so PHPUnit no longer triggers repeated malformed-regex warnings.
- Made RequirementsChecker temporary-directory cleanup recursive and Windows-safe.
- No runtime Membership behavior or database schema change in this checkpoint.


## 0.4.0-beta.13 - 2026-09-10

### Beta 2 — Memberships stabilization checkpoint
- Replaced the false-positive extension contract with method-scoped assertions so unrelated legacy SQL cannot fail the test.
- Scoped activation-marker contracts to `grant`, `replace`, and `schedule` instead of counting strings across an entire source file.
- Hardened legacy custom-day grants so future scheduled VIP grants cannot be mistaken for current active grants.
- Immediate custom-day grants now cancel an existing future VIP schedule and emit the matching cancellation event.
- Added `membership.extended` / `MembershipExtended` so extensions are distinguishable from new assignments for event consumers and Notifications.
- Admin activity logging now records assigning Free as a VIP revocation transition rather than a new membership assignment.
- Expanded Membership integration coverage across lifecycle, scheduling, Lifetime, dry-run maintenance, idempotency, replacement history, invalid input, health checks, overview/history, and legacy custom-day grants.
- Added `composer test:checkpoint` for the full PHPUnit + isolated integration pass.
- Added `composer check:release` for install, production, and Membership integrity checks.

### Scope
- No payment processing.
- No persisted `users.is_vip` boolean.
- No new Membership database migration in this checkpoint.


## 0.4.0-beta.12 - 2026-09-10

### Memberships QA stabilization
- Rebuilt the extension identity contract test explicitly so it verifies that `plan_key` is preserved.
- No runtime Membership behavior or database schema changes are included.


## 0.4.0-beta.11 - 2026-09-10

### Memberships QA stabilization
- Fixed the final stale Membership scheduling assertion: extension must preserve the current `plan_key`.
- The contract now explicitly rejects rewriting extensions to `vip-custom` and verifies the real expiration update.
- No runtime behavior or database schema changes are included.


## 0.4.0-beta.10 - 2026-09-10

### Memberships QA stabilization
- Reconciled stale Membership contract tests with the interface-based Membership boundary.
- Updated activation-marker coverage after extension hardening removed the old INSERT path.
- Strengthened the extension contract to verify that extension preserves plan identity.
- No Membership runtime behavior or database schema changes are introduced.


## 0.4.0-beta.9 - 2026-09-10

### Memberships validation
- Added `composer test:membership`, a focused unit + MySQL integration validation pass for the accumulated Memberships work.
- Added isolated Membership lifecycle integration coverage for Free, VIP assignment, extension, revocation, Lifetime, scheduling, cancellation, activation and expiration.
- Added idempotency assertions for scheduled activation and expiration events.
- Added `php bin/cms membership:check`, a read-only audit of the real installation's membership schema and grant integrity.
- Membership health checks detect duplicate active grants, duplicate future grants, active/future overlap, unknown plan keys, orphan grants and accidental persisted VIP boolean columns.


## 0.4.0-beta.8 - 2026-09-10

### Memberships stabilization
- Fixed an unbalanced Twig conditional in the membership detail screen.
- Free membership now renders explicitly as Free instead of falling through to an inactive-VIP presentation.
- Revoke controls are shown only for actual VIP memberships.
- Custom extension controls are now entirely scoped to active finite VIP memberships.
- Server-side extension now rejects Free accounts and Lifetime VIP instead of creating/changing membership unexpectedly.
- Invalid extension attempts return a controlled HTTP 422 response.
- Added regression coverage for the accumulated Beta 2 view/action inconsistencies.


## 0.4.0-beta.7 - 2026-09-10

### Membership stabilization
- Fixed Free membership being rendered as an expiring active VIP in the admin detail screen.
- Limited extension controls to finite active VIP memberships.
- Fixed dependency-container wiring introduced during the membership maintenance batches: AccountLifecycleService again receives only its own dependencies and DataPruner receives the activation/expiration processors.
- Fixed immediate extension inserts after adding the activation marker column.
- Current VIP revocation no longer silently revokes a separately scheduled future membership.
- Assigning Free or replacing membership cleans/reconciles an existing future schedule and emits schedule-cancellation lifecycle events when appropriate.
- Added idempotent `membership.activated` processing for scheduled grants that reach their start time.
- Historical/already-started grants are pre-marked by migration to avoid retroactive activation notifications.


## 0.4.0-beta.6 - 2026-09-10

### Memberships
- Added a membership status presenter for explicit Free, finite VIP, Lifetime and Scheduled operational states.
- Account settings now show pending future VIP activation and its configured period.
- Admin membership detail shows approximate days remaining and days until a scheduled activation.
- Added quick +30/+90/+365 day renewal shortcuts while retaining custom extensions.
- Added Scheduled VIP dashboard metric.
- Added Core lifecycle events and optional Notifications handling for membership scheduling and schedule cancellation.
- Membership history now distinguishes Scheduled records from active/granted and expired records.


## 0.4.0-beta.5 - 2026-09-10

### Memberships
- Added future VIP scheduling with explicit overlap prevention and one pending schedule per user.
- Added scheduled membership cancellation from the admin membership screen.
- Added additive VIP extension that preserves remaining time and current plan identity.
- Added a dedicated Scheduled filter/count in membership administration.
- Membership lookup now prioritizes the current active grant before scheduled/fallback history so future grants do not hide present access.
- Lifetime VIP rejects overlapping future schedules.
- No persisted `is_vip`/`vip_active` flag is introduced.


## 0.4.0-beta.4 - 2026-09-10

### Memberships
- Added Core lifecycle events for membership assignment, revocation and expiration.
- Added idempotent expiration processing through normal maintenance, without a persisted VIP boolean on users.
- Notifications can react to membership lifecycle changes through Core events.
- Added `expired_event_at` only as an event-delivery marker on entitlement grants.
- Preserved date/revocation-based access semantics and Free fallback behavior.


## 0.4.0-beta.3 - 2026-09-10

### Memberships
- Moved public profile, account access audience, user administration and bundled content visibility checks onto `MembershipManagerInterface`.
- Preserved legacy custom-day VIP grants through `MembershipManagerInterface::grantDays()` instead of calling entitlement persistence directly.
- Removed the accidental duplicate Memberships dependency from `UsersController`.
- Public profiles now expose the named active membership plan rather than a raw VIP boolean.
- Added an architectural regression test that forbids introducing `users.is_vip` / `vip_active` columns and keeps interactive consumers behind the membership contract.


## 0.4.0-beta.2 - 2026-09-10

### Memberships
- Added `MembershipManagerInterface` so account/core consumers can use membership state without depending on entitlement persistence.
- Normalized membership status: every account now resolves to an explicit Free or active VIP membership state.
- Account profile now shows the named membership plan and expiration/lifetime status.
- Added first-class membership dashboard metrics, expiring-within-7-days attention, and a membership quick action.
- Removed dashboard VIP-expiration coupling from the user-creation permission path.


## 0.4.0-beta.1 - 2026-09-10

### Memberships
- Began Beta 2 with a first-class membership administration surface built on the existing VIP entitlement layer.
- Added Free, VIP 30-day, VIP 90-day, annual and lifetime plan management through the existing MembershipPlanCatalog/Service.
- Added `memberships.manage`, granted to Super Administrator by default.
- Added `/admin/memberships` with status/search filters, expiring-soon/lifetime visibility and manual assign/revoke actions.
- Added per-user membership detail/history including plan, source, note, period and granting administrator.
- Existing `vip` content audiences remain compatible and continue using the same entitlement checks.
- No payment processing is included.


## 0.3.0-beta.6 - 2026-09-10

### Production hardening
- Added `release:smoke`, a pre-bootstrap distribution smoke check for version metadata, bundled module manifests, migration files and private storage guards.
- Strengthened `release:check` to require upload/private Apache guards and production/installation documentation.
- Added release-package regression coverage so incomplete deployment archives fail before application bootstrap.
- Documented a final deployment smoke workflow for fresh installs and upgrades.


## 0.3.0-beta.5 - 2026-09-10

### Production hardening
- Fresh-install failures now roll back NovaNuke-owned tables and incomplete `.env` state after the installer has verified an empty database.
- `.env` is re-secured after atomic activation and production readiness validates owner-only permissions on POSIX hosts.
- Added `storage/private/.htaccess` as a defense-in-depth deny rule for Apache/shared-hosting misconfiguration.
- Database and file backup writers reject symlinked backup directories.
- Backup verification rejects non-regular/readable files and overly permissive POSIX backup permissions.
- Expanded production/shared-hosting checks and installer/backup regression coverage.


## 0.3.0-beta.4 - 2026-09-10

### Production hardening
- Added request-shape limits for excessive query/body parameter counts and deeply nested input.
- Production error responses continue to hide exception details, while log paths are normalized to `[APP]/...` instead of absolute project paths.
- Explicit authentication/account throttles now return HTTP 429 with `Retry-After`.
- Added CSRF/state-changing-route contracts for core/admin surfaces.
- Added abuse, disclosure, throttle and CSRF regression tests.


## 0.3.0-beta.3 - 2026-09-10

### Production hardening
- Added a shared storage-boundary guard for private file roots and resolved files.
- Downloads, avatars and Wiki attachments now reject symlinked storage/file paths consistently.
- Media deletion now uses the same containment guard instead of module-specific path logic.
- Added contract tests that private Downloads/Wiki files are authorized before their physical path is resolved.
- Preserved avatars as intentionally public assets with explicit inline/cache/nosniff headers.


## 0.3.0-beta.2 - 2026-09-10

### Production hardening
- Fixed fresh installation requiring manual creation of `storage/private/downloads` and `storage/private/backups`.
- Installer preflight now provisions and validates the complete runtime storage layout.
- Rejects symlinked required storage boundaries and keeps private download storage self-healing.
- Fresh installer environment now records the Beta 1 session timeout/rotation defaults.


## [0.3.0-beta.1] - 2026-09-10

- Began Beta 1 production hardening without adding end-user features.
- Added configurable session absolute lifetime, idle timeout and session-ID rotation, plus strict SameSite validation.
- Added no-store/private defaults for admin, account, authentication and error responses.
- Added cross-origin browser hardening headers and stronger Apache/shared-hosting upload protections.
- Expanded `production:check` to validate session policy and shipped Apache guard files.
- Added direct upgrade support for `0.2.0-alpha.60` → `0.3.0-beta.1`.

## [0.2.0-alpha.60] - 2026-09-10

### Added
- Added `php bin/cms module:inspect MODULE` to expose a static human-readable inventory of a module manifest, dependencies, permissions, routes, event listeners/dispatches, migrations, catalogues and documentation state.
- Added `php bin/cms module:list` for a compact inventory of every on-disk module and its contract counts.
- Added module-inspection and Developer CLI contract coverage so both commands remain deterministic and pre-bootstrap.

### Changed
- Completed Phase 5 — Developer Experience by documenting the make → check → inspect → test → install workflow and the intended use of the bundled Quotes reference module.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.59` → `0.2.0-alpha.60` path.

## [0.2.0-alpha.59] - 2026-09-10

### Added
- Added `php bin/cms module:check MODULE`, a no-database/non-booting module preflight for manifest, provider, compatibility, migration, catalogue, documentation and route-name diagnostics before installation.
- Added developer CLI and module diagnostic contract tests covering safe pre-bootstrap behavior and common invalid-module failures.

### Changed
- `module:make` now writes `cms_min_version` from `Version::CURRENT` instead of a release-specific hardcoded string.
- Expanded Developer Experience documentation with the recommended make → check → install workflow and clarified PASS/WARN/FAIL semantics.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.58` → `0.2.0-alpha.59` path.

## [0.2.0-alpha.58] - 2026-09-10

### Added
- Began Phase 5 — Developer Experience with `php bin/cms module:make NAME`, a no-database scaffolder for safe Module API 1.0 starter modules.
- Added the official Quotes reference module demonstrating a manifest, migration, repository binding, named public/admin routes, permission checks, CSRF, activity logging, Twig namespaces, translations and the Admin menu extension hook.
- Added scaffolder and reference-module contract tests plus dedicated developer-experience documentation.

### Changed
- Module documentation now separates the minimal generated scaffold from the richer Quotes reference implementation and documents the recommended copy/extend workflow.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.57` → `0.2.0-alpha.58` path.

## [0.2.0-alpha.57] - 2026-09-10

### Changed
- Added `NovaNuke\Core\Events\EventName` as the canonical Core/bundled event-name contract while keeping the Module API 1.0 dispatcher string based.
- Moved profile action/statistics extension payload contracts into Core with compatibility subclasses under the historical Auth namespace.
- Added a Core-owned `ContentChanged` payload and made News, Pages and Wiki lifecycle payloads compatible subclasses for generic `content.created` / `content.updated` listeners.
- Migrated bundled dispatch/listen sites away from duplicated magic event strings and documented the event/payload matrix.
- Added an event API architecture test and corrected stale Friends assertions left behind by the alpha.56 optional-service refactor.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.56` → `0.2.0-alpha.57` path.

## [0.2.0-alpha.56] - 2026-09-10

### Changed
- Moved optional Comments, Media and Private Messages consumer contracts into Core-owned namespaces and bound the concrete module services behind those contracts.
- Moved cross-module integration payloads for comment targets/creation, media usage, friend requests/acceptance and private-message delivery into Core.
- Migrated bundled consumers and Notifications listeners to Core contracts so optional modules no longer import each other's implementation internals.
- Kept the previous module-owned integration payload names as runtime compatibility aliases for Module API 1.0.
- Documented Demo Content as the intentional orchestration exception because it builds a deterministic dataset through concrete optional-module services.

### Tests
- Expanded inter-module architecture checks and added optional-service compatibility tests.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.55` → `0.2.0-alpha.56` path.

## [0.2.0-alpha.55] - 2026-09-10

### Changed
- Moved shared Search provider interfaces/DTOs/registration payload and the Sitemap collection payload into Core-owned extension-contract namespaces.
- Migrated bundled News, Pages, Downloads and Wiki integrations to the Core contracts so optional Search/SEO implementations no longer own their consumers' public types.
- Kept the former module-owned Search and Sitemap class names as compatibility aliases for Module API 1.0.
- Added a Core `SearchProviderRegistryInterface`; the Search module registry implements it while remaining the concrete runtime implementation.

### Tests
- Added Core extension-contract and inter-module dependency tests to prevent bundled content modules from regressing to Search/SEO implementation imports.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.54` → `0.2.0-alpha.55` path.

All notable NovaNuke changes will be documented here.

## [0.2.0-alpha.54] - 2026-09-10

### Changed
- Began Phase 4 — Module Contracts / Internal API with fail-fast validation for the documented module API 1.0 manifest rules.
- Module, CMS/PHP minimum and dependency versions now require complete semantic versions; permissions must remain inside the declaring module namespace; duplicate permissions/events are rejected; providers must belong to their module directory namespace.
- Router registration now rejects duplicate route names and exact overlapping HTTP-method/path collisions so a module cannot silently shadow an existing handler.
- Kept `ModuleApi::VERSION`, `ModuleInterface`, `ModuleContext` and documented public signatures unchanged.

### Tests
- Expanded manifest and router contract coverage and added `BundledModuleContractTest` to validate every shipped module manifest/provider.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.53` → `0.2.0-alpha.54` path.

## [0.2.0-alpha.53] - 2026-09-10

### Changed
- Completed the shared Admin navigation/header consistency pass across General Settings, User Settings, System Information, Activity Logs, Menus, Modules and Themes.
- Extended the same shared breadcrumbs/page-header pattern to Comments, Demo Content, Media, Polls, Private Message Reports, Search, Statistics and the Wiki administration family.
- Promoted existing Wiki index actions into the shared page header and removed redundant legacy return links from completed Admin screens.
- Intentionally left the Admin dashboard custom and Blocks untouched because Blocks remains frozen outside critical regressions.

### Tests
- Added `AdminExperienceCompletionTest` to protect the completed Admin surface and explicitly document the dashboard/Blocks exclusions.
- Updated release and upgrade contracts for the direct `0.2.0-alpha.52` → `0.2.0-alpha.53` path.

## [0.2.0-alpha.52] - 2026-09-10

### Changed

- News, Pages, Downloads and Web Links Create/Edit screens now use the shared Admin breadcrumb and page-header components.
- Redundant Return-to-list links were removed from those editors because breadcrumbs provide consistent parent navigation.
- Web Links now uses a context-aware browser title for Create versus Edit instead of always saying Edit.
- Existing editor forms retain their current POST/CSRF behavior, routes, publication/access controls and authorization rules.

### Compatibility

- Alpha.52 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.51 as a documented source.

## [0.2.0-alpha.51] - 2026-09-10

### Changed

- Create User, Manage User and Role Permissions screens now use the shared Admin breadcrumb and page-header components.
- Redundant Return-to-list links were removed because the breadcrumb provides consistent parent navigation.
- Existing account, VIP, password and role-permission forms retain their current POST/CSRF behavior and authorization rules.

### Compatibility

- Alpha.51 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.50 as a documented source.

## [0.2.0-alpha.50] - 2026-09-10

### Changed

- Users and Roles now use the shared Admin breadcrumb, page-header, row-action and empty-table components.
- The Users list keeps its VIP filters while moving account creation into the shared page-header action area.
- The Create account shortcut is hidden unless the current administrator already satisfies the existing `users.manage`, `users.assign_roles` and Super Administrator requirements.

### Compatibility

- Alpha.50 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.49 as a documented source.

## [0.2.0-alpha.49] - 2026-09-10

### Added

- A dashboard shortcut to the existing manual account-creation screen for authorized Super Administrators.
- A priority-queue warning when active VIP access will expire within seven days.

### Security

- The Create account shortcut requires the same user-management, role-assignment and Super Administrator checks as the protected destination.
- VIP expiration counts include distinct users only and exclude expired or revoked entitlements.

### Compatibility

- Alpha.49 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.48 as a documented source.

## [0.2.0-alpha.48] - 2026-09-10

### Added

- Open Comment, Download, Web Link and Private Message reports now appear in the dashboard priority queue.
- Web Links joins the permission-aware dashboard content-creation shortcuts.

### Security

- Every report counter requires its module to be enabled, its table to exist and the current administrator to hold that module's moderation permission.
- Dashboard links reuse the existing protected report-management screens and do not expose report content.

### Compatibility

- Alpha.48 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.47 as a documented source.

## [0.2.0-alpha.47] - 2026-09-10

### Added

- A compact Site health section for maintenance mode, migrations/module updates, writable storage and production configuration.
- Safe operational summaries linked to the existing protected Settings and System Information screens.

### Changed

- NovaModern now gives dashboard health, attention and quick-action cards its native light palette.

### Security

- Site-health inspection and output require `settings.manage` and expose only summarized state, never credentials or internal paths.
- Dynamic health values continue through Twig's default output escaping.

### Compatibility

- Alpha.47 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.46 as a documented source.

## [0.2.0-alpha.46] - 2026-09-10

### Added

- A permission-aware dashboard priority queue for module problems, comments awaiting moderation and unpublished primary content.
- Direct Create actions for News, Pages and Downloads when their modules and permissions are available.
- Deterministic priority ordering with module issues and moderation work shown before unpublished content.

### Security

- Dashboard data and actions remain gated by the existing module state and server-side permissions.
- Dynamic labels, counts and URLs continue through Twig's default output escaping.

### Compatibility

- Alpha.46 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.45 as a documented source.

## [0.2.0-alpha.45] - 2026-09-10

### Added

- Shared accessible components for empty Admin table rows and per-record link actions.
- Useful empty-state explanations and Create actions in the primary content tables.
- Edit and View actions for published News, Pages, Downloads and Web Links records.

### Security

- Draft or otherwise unpublished records do not receive public View links.
- Web Links deletion remains a POST form with CSRF token and explicit confirmation.

### Compatibility

- Alpha.45 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.44 as a documented source.

## [0.2.0-alpha.44] - 2026-09-10

### Changed

- News, Pages, Downloads and Web Links Admin lists now share the same breadcrumb and page-header components.
- Create and View-site actions occupy one predictable header location.
- Redundant Return-to-dashboard links were removed because the breadcrumb now provides that route consistently.

### Compatibility

- Existing Admin controllers and protected Create routes are reused without changing authorization behavior.
- Alpha.44 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.43 as a documented source.

## [0.2.0-alpha.43] - 2026-09-10

### Added

- Shared public page-header component with consistent title, eyebrow, description and contextual-action regions.
- Permission-aware Manage shortcuts on the News, Pages, Downloads and Web Links directories.
- RSS and link-submission actions remain available from their respective directory headers.

### Security

- Public controllers expose Manage links only after checking their existing module permission through `AuthorizationService`.
- Admin controllers continue to enforce authorization server-side; header visibility is not treated as access control.

### Compatibility

- Alpha.43 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.42 as a documented source.

## [0.2.0-alpha.42] - 2026-09-10

### Added

- Shared Twig components for accessible empty states and public pagination.
- Consistent empty results in News, Pages, Downloads and Web Links.
- Current-page semantics through `aria-current` and descriptive pagination labels.

### Changed

- Downloads and Web Links retain encoded search and ordering filters while paging.
- Empty Web Links results provide a direct link to the existing submission form.

### Compatibility

- The shared components use existing theme-independent view loading and work with Default, Classic and NovaModern.
- Alpha.42 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.41 as a documented source.

## [0.2.0-alpha.41] - 2026-09-09

### Added

- One accessible, autoescaped Twig breadcrumb component for public content navigation.
- Consistent Home/list/detail trails for News, Pages, Downloads and Web Links.
- Page trails retain the visible parent page for both default and landing templates.

### Compatibility

- Breadcrumbs use the existing shared CSS and therefore work in Default, Classic and NovaModern without theme-specific copies.
- Alpha.41 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.40 as a documented source.

## [0.2.0-alpha.40] - 2026-09-09

### Added

- `php bin/cms upgrade:complete --from=VERSION` records a successful Core upgrade only after migration, module-version and distribution checks pass.
- Fresh installations record `system.core_version` and `system.core_updated_at` as part of their initial settings.
- Upgrade preflight compares the operator-declared source with the recorded installed Core version when available.

### Safety

- Completion refuses downgrades, malformed versions, mismatched recorded sources, pending/missing migrations, outstanding module updates and failed release checks.
- Legacy installations without a recorded Core version receive a visible bootstrap warning before Alpha.40 initializes the value.
- `storage/installed.lock` remains the immutable installer lock and is not repurposed as mutable upgrade state.

### Compatibility

- Alpha.40 adds no database migration, module/theme update or Composer dependency; version state uses the existing settings table.
- Direct upgrade preflight now includes Alpha.39 as a documented source.

## [0.2.0-alpha.39] - 2026-09-09

### Changed

- `upgrade:check` now performs full SQL, TAR and matched-pair backup verification instead of accepting merely recent non-empty files.
- Verified database and file backups must also be regular, non-symlinked files created within the last 24 hours.
- A mismatched, corrupted, truncated or structurally invalid backup pair is now a required preflight failure.

### Compatibility

- Alpha.39 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.38 as a documented source.

## [0.2.0-alpha.38] - 2026-09-09

### Added

- Read-only `php bin/cms backup:verify` command for the latest database and file backup pair.
- SQL backup envelope, completeness, size and SHA-256 verification.
- Streaming TAR verification covering headers, checksums, terminator, regular entries, safe paths and every manifest-declared size/hash.
- Matched-pair check requiring individually valid database/file backups created no more than ten minutes apart.

### Security

- Verification never executes SQL, extracts archive entries or accepts an administrator-supplied filesystem path.
- Symlinked backups, traversal/absolute paths, duplicate entries, malformed manifests, content/manifest mismatches and trailing archive data are rejected.

### Compatibility

- Alpha.38 adds no database migration, module/theme update or Composer dependency.
- Direct upgrade preflight now includes Alpha.37 as a documented source.

## [0.2.0-alpha.37] - 2026-09-09

### Changed

- Core and module migration runners now refuse to continue when files for previously executed migrations are missing.
- A failed migration stops the current batch immediately and identifies the exact core or module migration that failed.
- The CLI reports a restore-first recovery path instead of suggesting an unsafe automatic DDL rollback.

### Safety

- Failed migrations are not marked as completed and no later migration in the batch is executed.
- Recovery documentation requires restoring the pre-upgrade database and application files as one matched checkpoint.
- Operators are explicitly warned not to delete migration-history rows or retry partially applied DDL blindly.

### Compatibility

- Alpha.37 adds no database migration, module/theme update or Composer dependency.
- The read-only upgrade preflight now accepts Alpha.36 as a documented direct source in addition to Alpha.33–Alpha.35.

## [0.2.0-alpha.36] - 2026-09-09

### Added

- Read-only `php bin/cms upgrade:check --from=VERSION` preflight for direct upgrades from Alpha.33, Alpha.34 and Alpha.35.
- Required checks for preserved `.env` and installation lock, recent database/file backups, supported direction and complete executed migration files.
- Explicit warnings for pending migrations and installed module updates without executing either operation.

### Security

- Source versions use strict syntax and an explicit support list; undocumented direct upgrades and downgrades are blocked.
- Backup discovery accepts only recent regular `.sql`/`.tar` files in private backup storage and ignores symlinks.

### Compatibility

- Existing installations need no migration, module/theme update or Composer change.
- Releases older than Alpha.33 require their documented intermediate path or a clean installation; Alpha.36 does not pretend those direct upgrades were tested.

## [0.2.0-alpha.35] - 2026-09-09

### Added

- `php bin/cms install:check` for checking PHP extensions, writable paths and absence of prior installation files before opening the web installer.
- Focused tests for installer URL/host validation, `.env` preservation, requirement state and safe database ordering.

### Security

- Installation accepts only HTTP/HTTPS site URLs without embedded credentials, query strings, fragments or control characters.
- Database hosts reject DSN separators and connection options.
- The installer never overwrites an existing `.env` and refuses to migrate a database that already contains tables.

### Compatibility

- Existing installations need no migration, module update, theme update or Composer change.
- `install:check` is a pre-installation diagnostic; Super Administrator credentials remain exclusive to the web installer and are not accepted as command-line arguments.

## [0.2.0-alpha.34] - 2026-09-09

### Added

- Optional official Demo Content module with the stable `novatech-community-v1` fictional technology-community dataset.
- Twelve fictional accounts, active and expired VIP examples, and realistic module data created only for active News, Pages, Downloads, Web Links, Comments, Polls, Friends and Private Messages modules.
- Dataset ownership records that prevent duplicate installation and prepare a future controlled Remove/Reset lifecycle.
- Super-Administrator installation screen under **Admin → System → Demo content**, plus dataset contract and isolated integration coverage.

### Security

- Demo installation requires `settings.manage`, an explicit Super Administrator check, POST, CSRF and confirmation, and records an Activity Log event.
- Existing records are never overwritten; failed installation cleanup targets only IDs already attributed to this dataset.
- Search and Statistics continue using their normal provider/derived-data paths. The demo installer creates neither synthetic search rows nor Wiki or Block content.

### Compatibility

- The core version advances to 0.2.0-alpha.34. Demo Content 1.0.0 requires this release and has no Composer dependency.
- Existing alpha.33 installations need no core migration; Demo Content creates its ownership tables only when the optional module is installed.

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

- RC.4: fixed NN-SEC-01 by enforcing parent-content audience checks across Comments list/create/edit/react/report and hiding inaccessible comment IDs behind 404.
- RC.4: fixed NN-HTTP-01 by restricting `Response::redirect()` to safe local absolute paths and rejecting scheme-relative URLs, backslashes and control characters.
