# NovaNuke 0.2.0-alpha.58

Alpha.58 begins Phase 5 — Developer Experience.

## Highlights

- Added `php bin/cms module:make NAME` to generate a safe minimal Module API 1.0 structure without booting the application or touching the database.
- Added the official `Quotes` reference module with a public page, administrator workflow, migration, repository, permissions, CSRF protection, activity logging, translations and Admin menu integration.
- Added contract tests for the scaffolder and reference module.
- Added `docs/DEVELOPER_EXPERIENCE.md` and expanded module-development guidance.
- Updated the direct upgrade contract for `0.2.0-alpha.57` → `0.2.0-alpha.58`.

## Database impact

Core has no new migration. Quotes includes its own module migration, which runs only when the module is installed.

## Compatibility

Module API remains `1.0`. Existing modules do not need to use the scaffolder or depend on Quotes.
