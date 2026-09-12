# NovaNuke 0.2.0-alpha.57

Alpha.57 completes the primary Module Contracts / Internal API hardening pass around events and extension hooks.

## Highlights

- Added `NovaNuke\Core\Events\EventName` as the canonical set of Core/bundled event identifiers while preserving the string-based Module API 1.0 dispatcher.
- Moved public profile action/statistic extension payload contracts into `NovaNuke\Core\Profile` with legacy Auth compatibility subclasses.
- Added `NovaNuke\Core\Content\ContentChanged` so generic `content.created` and `content.updated` listeners can consume News, Pages and Wiki through one Core-owned payload contract.
- Kept the historical News, Pages and Wiki payload classes as compatible subclasses.
- Migrated Core and bundled PHP dispatch/listen calls away from duplicated magic event strings.
- Added `EventApiContractTest` to enforce event-name uniqueness, shared payload compatibility and the no-magic-event-string guardrail.
- Updated stale Friends module tests to assert the alpha.56 `PrivateMessageComposerInterface` contract rather than the old concrete service.
- Updated the direct upgrade contract for `0.2.0-alpha.56` → `0.2.0-alpha.57`.

No migrations, database schema changes, Composer dependency changes or visible UI features are included.
