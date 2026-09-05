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
    en/
    es/
  tests/
```

Composer maps `Modules\` to `modules/`. A provider stored at `modules/Example/src/ExampleModule.php` therefore uses the class name `Modules\Example\src\ExampleModule`.

## Manifest

```json
{
  "name": "Example",
  "slug": "example",
  "version": "1.0.0",
  "description": "Example module.",
  "author": "Developer",
  "provider": "Modules\\Example\\src\\ExampleModule",
  "cms_min_version": "0.1.0",
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
- Dependencies map module slugs to minimum installed versions.
- Permission slugs must start with the module slug followed by a dot.
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

`register()` runs before `boot()` for that module. Do not execute schema changes in either method; use migrations. Do not modify core files.

The context exposes the manifest, service container, router, event dispatcher and absolute module base path.

## Module views

Register a Twig namespace:

```php
$context->container
    ->get(ViewRenderer::class)
    ->addNamespace('example', $context->basePath . '/views');
```

Render it as `@example/page.twig`. Twig escapes HTML output by default.

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

## Migrations

Migration files return an object implementing `NovaNuke\Core\Database\Migration`. Filenames must be unique within the module and sort chronologically.

Disabling a module never removes data. Uninstalling offers two explicit choices:

- preserve tables and migration history;
- call every module migration's `down()` method in reverse order and delete data.

An update runs only pending migrations and updates the installed semantic version. Back up the database before updating production modules.

## Lifecycle states

- Available: files detected, not installed.
- Installed/disabled: migrations complete, code does not boot.
- Enabled: provider registers and boots on each request.
- Update available: disk version is newer than installed version.
- Missing files: installed record exists but its directory is absent.
- Error: the last provider boot failed; details appear in the modules panel and application log.

The `Welcome` module bundled with NovaNuke is the executable reference implementation.
