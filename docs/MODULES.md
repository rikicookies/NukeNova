# Developing NovaNuke modules

NovaNuke modules are trusted PHP packages copied manually into `modules/`. Installing a module is equivalent to allowing that code to run with the application's database and filesystem privileges. Install modules only from sources you trust.

The administrative panel never accepts PHP uploads. Files must be placed on the server by an authorized operator before NovaNuke can detect them.

## Start with the scaffolder

For a new module, generate a minimal Module API 1.0 structure instead of copying an unrelated production module:

```bash
php bin/cms module:make "Reading List"
```

The command does not boot NovaNuke, connect to the database, install the module or overwrite an existing directory. It creates a valid manifest/provider/view/language skeleton and empty migration/test directories. See `docs/DEVELOPER_EXPERIENCE.md`.

For a complete small example, read `modules/Quotes/`. Quotes adds a module-owned migration, repository, public/admin routes, authorization, CSRF, activity logging and an Admin menu entry while remaining independent of other modules.

## Structure

```text
modules/Example/
  module.json
  src/
    ExampleModule.php
    Controllers/
    Services/
  database/
    migrations/
  views/
  assets/
  language/
    en.json
    es.json
  tests/
```

Composer maps `Modules\` to `modules/`. A provider stored at `modules/Example/src/ExampleModule.php` therefore uses the class name `Modules\Example\src\ExampleModule`.

## Manifest

```json
{
  "name": "Example",
  "slug": "example",
  "version": "1.0.0",
  "api_version": "1.0",
  "description": "Example module.",
  "author": "Developer",
  "provider": "Modules\\Example\\src\\ExampleModule",
  "cms_min_version": "0.1.0-alpha.1",
  "php_min_version": "8.3.0",
  "dependencies": {
    "another-module": "1.2.0"
  },
  "permissions": [
    "example.view",
    "example.manage"
  ]
}
```

- Slugs use lowercase letters, numbers and hyphens.
- Module, CMS minimum, PHP minimum and dependency versions use complete semantic versioning.
- `api_version` selects the stable NovaNuke module contract; Beta 1 supports API 1.0.
- Dependencies map module slugs to minimum installed versions.
- Permission slugs must start with the module slug followed by a dot, use lowercase dot-separated identifiers, and cannot be duplicated.
- Event names use lowercase dot-separated identifiers with letters, numbers and hyphens; duplicate declarations are rejected. Declare emitted and consumed events in the optional `events` array.
- The provider must use the module directory's own `Modules\<Directory>\...` PHP namespace and implement `ModuleInterface`.

## Provider lifecycle

```php
final class ExampleModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        // Register services and Twig namespaces.
    }

    public function boot(ModuleContext $context): void
    {
        // Register routes and event listeners.
    }
}
```

NovaNuke completes `register()` for every dependency-safe module before beginning the `boot()` pass. Do not execute schema changes in either method; use migrations. Do not modify core files. The frozen compatibility surface is listed in `docs/API_STABILITY.md`.

The context exposes the manifest, service container, router, event dispatcher and absolute module base path.

## Module views

Register a Twig namespace:

```php
$context->container
    ->get(ViewRenderer::class)
    ->addNamespace('example', $context->basePath . '/views');
```

Render it as `@example/page.twig`. Twig escapes HTML output by default.

## Module translations

Enabled modules automatically receive the manifest slug as a translation namespace. Put flat JSON catalogues in `language/en.json` and `language/es.json`, then use `{{ trans('example::page.title') }}` in Twig. See `docs/INTERNATIONALIZATION.md` for fallback and placeholder rules.

## Routes

Register routes during `boot()`:

```php
$context->router->get('/example', $handler, 'example.index');
```

Core routes are registered first. Exact HTTP-method/path collisions and duplicate named routes are rejected at registration time. Modules should still use a unique URL prefix and namespaced route names to avoid conflicts.

An installed module also has an administrator-selected audience: public, guests, registered members or active VIP members. The router records which module owns each route, and the HTTP kernel enforces that audience on public module routes. Administrative routes continue to rely on their explicit permissions. A module must still authorize individual records when it supports mixed audiences inside the same module.

## Events and hooks

Core and bundled modules should reference shared event identifiers through `NovaNuke\Core\Events\EventName`. The dispatcher remains string based for Module API 1.0 compatibility; module-specific custom events may still use their manifest-declared strings. See `docs/EVENTS.md` for the Core-owned event/payload matrix.


Listeners are synchronous and ordered from highest to lowest priority:

```php
$context->events->listen('page.rendering', function (PageRendering $event): void {
    // Modify only the documented event payload.
}, priority: 10);
```

Dispatch typed payload objects:

```php
$context->events->dispatch('example.created', new ExampleCreated($id));
```

Do not put passwords, tokens, PDO connections or complete requests into event payloads.

The manifest preserves an optional event declaration:

```json
"events": ["example.created", "user.registered"]
```

This list documents the module contract for diagnostics; listeners and dispatches are still registered explicitly by the provider.

Core authentication notifications include `user.registered`, `user.email_verified`, `user.logged_in`, `user.email_changed` and `user.anonymized`. They carry only the documented numeric identity and minimal state. See `docs/AUTH_EVENTS.md`.

Public profiles expose `profile.actions.building` and `profile.statistics.building`. Optional modules should add only internal action URLs and privacy-safe aggregate values. Friends demonstrates both contracts without making Profiles depend on its tables.

Searchable content modules can listen to `search.providers.registering` and add a provider implementing `NovaNuke\Core\Search\SearchProviderInterface`. Search DTOs, the registration payload and `LikePattern` live under `NovaNuke\Core\Search` so modules do not depend on Search implementation classes. The provider is responsible for publication and viewer-access checks. See `docs/SEARCH.md` for the complete contract.

Sitemap contributors listen to `sitemap.collecting` with `NovaNuke\Core\Sitemap\SitemapCollecting`. SEO owns sitemap rendering, but Core owns the extension payload so contributors remain decoupled from SEO internals.

## Migrations

Migration files return an object implementing `NovaNuke\Core\Database\Migration`. Filenames must be unique within the module and sort chronologically.

Disabling a module never removes data. Uninstalling offers two explicit choices:

- preserve tables and migration history;
- call every module migration's `down()` method in reverse order and delete data.

An update runs only pending migrations and updates the installed semantic version. Back up the database before updating production modules.

New module migrations must implement `NovaNuke\Core\Database\RecoverableMigration`, make every DDL/data step safe to repeat, and declare or implement verifiable `isApplied()` / `isRolledBack()` postconditions. Bundled migrations may use `VerifiesMigrationState` plus `MIGRATION_TABLES`, `MIGRATION_COLUMNS`, `MIGRATION_INDEXES`, and `MIGRATION_VALUES`; complex data coverage should use explicit methods. Core and modules share the same database advisory lock and durable operation ledger. After an interrupted install, update, or uninstall, use `php bin/cms migrate:recover --module=SLUG`; never repair module history by hand.

`php bin/cms migrate:status` lists pending and missing migration files for every installed module. It also reports interrupted `running`/`dirty` operations and when the copied manifest version is newer than the installed database record. Status inspection never executes migrations or changes module state.

## Lifecycle states

- Available: files detected, not installed.
- Installed/disabled: migrations complete, code does not boot.
- Enabled: provider registers and boots on each request.
- Update available: disk version is newer than installed version.
- Missing files: installed record exists but its directory is absent.
- Error: the last provider boot failed; details appear in the modules panel and application log.

The `Welcome` module remains the smallest lifecycle demonstration. The `Quotes` module is the recommended small-but-complete reference implementation for new module development.

## Optional services and cross-module events

Optional integrations must depend on Core-owned contracts rather than another module's `src` implementation classes. During `register()`, a provider module binds its Core interface; during `boot()`, consumers may call `Container::has()` for that interface before using it.

Bundled examples include `NovaNuke\Core\Comments\CommentProviderInterface`, `NovaNuke\Core\Media\MediaLibraryInterface`, and `NovaNuke\Core\Messaging\PrivateMessageComposerInterface`. Cross-module event payloads likewise use Core-owned classes such as `CommentCreated`, `CommentTargetChecking`, `MediaUsageChecking`, `FriendRequested`, `FriendAccepted`, and `PrivateMessageSent`.

Do not import `Modules\OtherModule\src\...` from a normal bundled module. Demo Content is the intentional exception because it coordinates concrete services from several optional modules to install a deterministic sample dataset.


## Developer preflight

Before installing a new module, run `php bin/cms module:check DirectoryName` (or pass its slug). The diagnostic is intentionally pre-bootstrap and non-executing: it validates module structure and declared contracts without running provider code or migrations. Treat FAIL results as installation blockers and WARN results as review items. See `docs/DEVELOPER_EXPERIENCE.md`.
