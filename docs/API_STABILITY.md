# Module API stability

NovaNuke 0.1.0-beta.1 defines module API **1.0**. A module declares `"api_version": "1.0"` in `module.json`; manifests without the field are treated as 1.0 for compatibility with alpha packages. NovaNuke rejects a different major version or a newer unsupported minor version before installation, updating or enabling.

## Stable surface

The 1.0 compatibility promise covers these public contracts:

- `ModuleInterface::register()` and `ModuleInterface::boot()`;
- Core-owned extension event names exposed by `NovaNuke\Core\Events\EventName` and the documented payload contracts in `docs/EVENTS.md`;
- the readonly `ModuleContext` properties `manifest`, `container`, `router`, `events` and `basePath`;
- `Migration::up()` and `Migration::down()`;
- public `Router`, `Request`, `Response`, `Container`, `ViewRenderer`, `Translator` and `EventDispatcher` methods used in `docs/MODULES.md`;
- manifest keys documented in `docs/MODULES.md`;
- typed event payloads explicitly documented in module/core documentation;
- Core-owned extension contracts under `NovaNuke\Core\Search` and `NovaNuke\Core\Sitemap` documented for Module API 1.0.

Patch and minor NovaNuke releases may add optional methods, manifest fields or event data, but will not remove or change the meaning of the stable 1.0 surface. Internal repositories, concrete controllers and classes not documented as module APIs may change before NovaNuke 1.0.

## Lifecycle guarantee

NovaNuke resolves enabled dependencies, then calls `register()` for every viable module. Only after that pass completes does it call `boot()` in the same dependency-safe order. A module may therefore discover optional services during `boot()` without depending on database row order. Schema work remains forbidden in both phases.

Use `register()` for service and Twig namespace bindings. Use `boot()` for routes, listeners, menus and blocks. A failed registration prevents that module and its dependents from booting; failures are recorded without exposing stack traces publicly.

Administrative menu listeners may keep the original API:

```php
$menu->add('Example', '/admin/example', 'example.manage');
```

NovaNuke 0.2.0-alpha.6 adds two optional trailing arguments for a safe icon identifier and navigation group. Omitting them remains API 1.0 compatible.

## Compatibility policy

Changing the module API major version will require an explicit future NovaNuke release and migration guide. Module authors should test against the lowest declared `cms_min_version`, PHP 8.3 and the current stable release. `PublicModuleApiTest` protects the frozen core shapes, while manifest and lifecycle tests cover compatibility enforcement.


## Contract enforcement

Alpha.54 makes the documented 1.0 rules fail fast instead of accepting ambiguous manifests. Module, CMS minimum, PHP minimum and dependency versions must be complete semantic versions. Permission identifiers must belong to the declaring module namespace, duplicate permission/event declarations are rejected, and the provider class must live below that module's own `Modules\\<Directory>\\` namespace.

The Router also rejects an exact HTTP-method/path collision and duplicate non-null route names at registration time. A module that collides with an existing core or module route therefore fails its lifecycle boot instead of silently shadowing another handler. Registering GET and POST for the same path remains valid because their method sets do not overlap.

These checks do not change `ModuleApi::VERSION`, `ModuleInterface`, `ModuleContext`, or any documented public method signature; they enforce rules already documented as part of API 1.0.

## Core-owned extension contracts (alpha.55)

Search provider DTOs/interfaces and the sitemap collection event payload now live in Core rather than inside optional module namespaces. This makes the ownership match the API 1.0 stability promise: Search and SEO may be disabled or replaced without forcing content modules to import their internal classes.

The previous `Modules\Search\src\SearchProviderInterface`, `SearchQuery`, `SearchProviderResult`, `SearchResultItem`, `SearchProvidersRegistering`, `LikePattern` and `Modules\Seo\src\SitemapCollecting` names remain runtime compatibility aliases. New code should use the Core namespaces.


## Optional module service contracts (alpha.56)

Bundled modules no longer import another optional module's internal implementation classes for Comments rendering, Media browsing or Private Message compose availability. The stable integration points now live in Core as `CommentProviderInterface`, `MediaLibraryInterface` and `PrivateMessageComposerInterface`. Concrete module services bind those interfaces when the module is enabled, so consumers may discover them during `boot()` without knowing the provider implementation.

Cross-module event payloads used by Comments, Media, Friends, Private Messages and Notifications also live in Core: `CommentCreated`, `CommentTargetChecking`, `MediaUsageChecking`, `FriendRequested`, `FriendAccepted` and `PrivateMessageSent`. Former module-owned payload names remain runtime compatibility aliases for Module API 1.0. Demo Content is the documented exception to the no-cross-module-internals rule because its installer intentionally coordinates concrete services from multiple optional modules to build a deterministic sample dataset.

## Developer tooling

`php bin/cms module:make` is a convenience scaffolder introduced in alpha.58. Its generated modules target Module API 1.0, but the command-line scaffolder itself is not part of the frozen Module API compatibility surface.

`php bin/cms module:check` is a Developer Experience diagnostic introduced in alpha.59. Like `module:make`, the CLI command itself is not part of the frozen Module API 1.0 surface; the contracts it validates are.
