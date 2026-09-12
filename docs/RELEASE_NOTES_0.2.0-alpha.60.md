# NovaNuke 0.2.0-alpha.60

Alpha.60 completes Phase 5 — Developer Experience.

## Highlights

- Added `php bin/cms module:inspect MODULE` for a static contract/extension inventory.
- Added `php bin/cms module:list` for a compact on-disk module inventory.
- Both commands run before application bootstrap and do not require the database or execute module code.
- Inspection reports manifest/API data, dependencies, permissions, routes, event listeners/dispatches, migrations, catalogues and README state.
- Added inspector/CLI contract tests and completed the documented make → check → inspect → test → install workflow.
- Updated direct upgrade support for `0.2.0-alpha.59` → `0.2.0-alpha.60`.

No database migration, Composer dependency or Module API version change is introduced by this release.
