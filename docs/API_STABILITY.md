# Module API stability

NovaNuke 0.1.0-beta.1 defines module API **1.0**. A module declares `"api_version": "1.0"` in `module.json`; manifests without the field are treated as 1.0 for compatibility with alpha packages. NovaNuke rejects a different major version or a newer unsupported minor version before installation, updating or enabling.

## Stable surface

The 1.0 compatibility promise covers these public contracts:

- `ModuleInterface::register()` and `ModuleInterface::boot()`;
- the readonly `ModuleContext` properties `manifest`, `container`, `router`, `events` and `basePath`;
- `Migration::up()` and `Migration::down()`;
- public `Router`, `Request`, `Response`, `Container`, `ViewRenderer`, `Translator` and `EventDispatcher` methods used in `docs/MODULES.md`;
- manifest keys documented in `docs/MODULES.md`;
- typed event payloads explicitly documented in module/core documentation.

Patch and minor NovaNuke releases may add optional methods, manifest fields or event data, but will not remove or change the meaning of the stable 1.0 surface. Internal repositories, concrete controllers and classes not documented as module APIs may change before NovaNuke 1.0.

## Lifecycle guarantee

NovaNuke resolves enabled dependencies, then calls `register()` for every viable module. Only after that pass completes does it call `boot()` in the same dependency-safe order. A module may therefore discover optional services during `boot()` without depending on database row order. Schema work remains forbidden in both phases.

Use `register()` for service and Twig namespace bindings. Use `boot()` for routes, listeners, menus and blocks. A failed registration prevents that module and its dependents from booting; failures are recorded without exposing stack traces publicly.

## Compatibility policy

Changing the module API major version will require an explicit future NovaNuke release and migration guide. Module authors should test against the lowest declared `cms_min_version`, PHP 8.3 and the current stable release. `PublicModuleApiTest` protects the frozen core shapes, while manifest and lifecycle tests cover compatibility enforcement.
