# Developing NovaNuke modules

NovaNuke modules are trusted PHP packages copied manually into `modules/`. Installing a module is equivalent to allowing that code to run with the application's database and filesystem privileges. Install modules only from sources you trust.

The administrative panel never accepts PHP uploads. Files must be placed on the server by an authorized operator before NovaNuke can detect them.

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
- Versions use semantic versioning.
- `api_version` selects the stable NovaNuke module contract; Beta 1 supports API 1.0.
- Dependencies map module slugs to minimum installed versions.
- Permission slugs must start with the module slug followed by a dot.
- Event names use lowercase letters, numbers, dots and hyphens. Declare emitted and consumed events in the optional `events` array.
- The provider must use the `Modules` PHP namespace and implement `ModuleInterface`.

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

Core routes are registered first. Modules must use unique URL prefixes and route names.

## Events and hooks

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

Searchable content modules can listen to `search.providers.registering` and add a provider implementing `SearchProviderInterface`. The provider is responsible for publication and viewer-access checks. See `docs/SEARCH.md` for the complete contract.

## Migrations

Migration files return an object implementing `NovaNuke\Core\Database\Migration`. Filenames must be unique within the module and sort chronologically.

Disabling a module never removes data. Uninstalling offers two explicit choices:

- preserve tables and migration history;
- call every module migration's `down()` method in reverse order and delete data.

An update runs only pending migrations and updates the installed semantic version. Back up the database before updating production modules.

`php bin/cms migrate:status` lists pending and missing migration files for every installed module. It also reports when the copied manifest version is newer than the installed database record. Status inspection never executes migrations or changes module state.

## Lifecycle states

- Available: files detected, not installed.
- Installed/disabled: migrations complete, code does not boot.
- Enabled: provider registers and boots on each request.
- Update available: disk version is newer than installed version.
- Missing files: installed record exists but its directory is absent.
- Error: the last provider boot failed; details appear in the modules panel and application log.

The `Welcome` module bundled with NovaNuke is the executable reference implementation.
