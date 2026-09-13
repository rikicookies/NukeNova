# Developer experience

NovaNuke Module API 1.0 supports two complementary starting points: the CLI scaffold for a clean minimum and the bundled Quotes module for a realistic small feature.

## Generate a starter module

```bash
php bin/cms module:make "Reading List"
```

The command creates `modules/ReadingList/` with a valid `module.json`, provider, public Twig view, English/Spanish catalogues, migration and test directories, and a local README. It does **not** install or enable the module and it does not connect to the database.

The command refuses invalid names and refuses to overwrite an existing module directory. Generated permissions, route names and namespaces are derived from the module name and remain module-owned.

After generation:

1. inspect `module.json` and replace the placeholder author/description;
2. implement services in `src/` and repeat-safe, postcondition-verified schema changes in `database/migrations/`;
3. keep route names, permissions, translations and event names namespaced;
4. install and enable the module from Admin only after its migrations and permissions are ready;
5. add unit/contract tests before distributing it.

## Read Quotes when the minimal scaffold is not enough

`modules/Quotes/` is the official small-but-complete reference module. It demonstrates:

- a strict Module API 1.0 manifest;
- `register()` for Twig namespaces and service bindings;
- `boot()` for routes and event listeners;
- public and permission-protected administrator routes;
- a module-owned migration and repository;
- CSRF validation for writes;
- activity logging;
- `admin.menu.building` through `EventName`;
- English/Spanish catalogues;
- shared Admin presentation components;
- contract tests that keep the example aligned with Core.

Quotes deliberately has no optional dependency on another module. Cross-module integration examples belong in `docs/MODULES.md`, `docs/EVENTS.md` and the modules that actually use those contracts.

## What the scaffolder does not generate

It intentionally does not generate controllers, repositories, migrations with guessed schema, permissions beyond one `*.manage` permission, admin routes, event payloads or dependencies. Those are application decisions, and manufacturing placeholder architecture for all of them would make generated modules harder to understand and easier to misuse.

The CLI scaffolder is a developer convenience, not part of the frozen Module API 1.0 compatibility surface. The generated module itself uses the frozen API documented in `docs/API_STABILITY.md`.


## Preflight a module before installation

Use the static checker before installing or enabling newly written or third-party module code:

```bat
php bin/cms module:check Quotes
php bin/cms module:check quotes
```

The command accepts either the module directory name or its manifest slug. It runs before the application bootstrap and does not require a database connection. It does not `require` the provider, run migrations, install, enable or boot the module.

Checks include the strict Module API manifest contract, current CMS/PHP/API compatibility, declared dependencies available on disk, provider PSR-4 file/class shape, migration naming/contract markers, JSON catalogue validity and EN/ES top-level key parity when both are present, README presence and statically detectable named-route namespace violations.

`[FAIL]` means the module should not be installed until corrected. `[WARN]` is advisory and does not make the command fail; for example, a module with no language directory may be valid when it has no user-facing strings. `[PASS]` means that particular static check succeeded. A passing preflight is not a substitute for the module's PHPUnit/integration tests.

Recommended development loop:

1. `php bin/cms module:make "My Module"`
2. implement the module and its tests;
3. `php bin/cms module:check MyModule`;
4. run the module/unit test suite;
5. install it through the normal NovaNuke module lifecycle on a disposable development database.


## Inspect a module's extension surface

After `module:check` passes, use the static inspector when you need to understand what a module exposes or consumes without opening each source file:

```bat
php bin/cms module:inspect Quotes
```

The inspector reports manifest/API versions, provider, declared dependencies, permissions, statically detectable named routes, `EventName` listeners/dispatches, migration files, language catalogues and README presence. It runs before application bootstrap and does not connect to the database or execute module code.

Use the compact ecosystem inventory when reviewing the whole installation:

```bat
php bin/cms module:list
```

`module:list` prints one stable row per on-disk manifest with version/API and counts for dependencies, permissions and detected routes. It is intended for development/review, not as a replacement for the runtime Admin Modules screen.

Recommended Phase 5 workflow:

1. `php bin/cms module:make "My Module"`
2. implement the module and its tests;
3. `php bin/cms module:check MyModule`;
4. `php bin/cms module:inspect MyModule`;
5. run PHPUnit/integration tests;
6. install/enable through the normal lifecycle on a disposable development database.

Alpha.60 closes the planned Developer Experience phase. Further CLI work should be driven by concrete Beta hardening needs rather than adding tooling for its own sake.
