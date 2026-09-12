# NovaNuke 0.2.0-alpha.59

Alpha.59 continues Phase 5 — Developer Experience with a safe module preflight workflow.

## Highlights

- Added `php bin/cms module:check MODULE` for static module diagnostics before installation.
- The checker runs before application bootstrap and does not require the database, execute providers, run migrations, install or boot the module.
- Validates strict manifest rules, CMS/PHP/API compatibility, dependencies available on disk, provider PSR-4 shape, migration naming/contract markers, JSON catalogues, EN/ES key parity, README presence and statically detectable named-route namespaces.
- `module:make` now derives generated `cms_min_version` from `Version::CURRENT`, removing per-release hardcoding.
- Added diagnostic and Developer CLI contract coverage.
- Updated the direct upgrade contract for `0.2.0-alpha.58` → `0.2.0-alpha.59`.

No database migration, Composer dependency or public Module API version change is introduced by this release.
