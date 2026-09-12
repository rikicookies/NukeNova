# NovaNuke 0.2.0-alpha.55

Alpha.55 continues Phase 4 — Module Contracts / Internal API by clarifying ownership of extension contracts shared across optional modules.

## Highlights

- Search provider interfaces, DTOs, registration payload and LIKE helper now live under `NovaNuke\Core\Search`.
- `SearchProviderRegistryInterface` is Core-owned; the Search module keeps the concrete registry implementation.
- Sitemap contributions now use `NovaNuke\Core\Sitemap\SitemapCollecting`.
- News, Pages, Downloads and Wiki no longer import those extension contracts from Search/SEO module internals.
- Previous module-owned names remain compatibility aliases for Module API 1.0.
- No database migration, Composer dependency or visible feature change is included.

## Upgrade

Run `php bin/cms upgrade:check --from=0.2.0-alpha.54`, the normal unit/integration/release checks, then `php bin/cms upgrade:complete --from=0.2.0-alpha.54`.
