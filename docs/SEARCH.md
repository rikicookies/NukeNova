# Search module

Search 1.0.0 provides `/search` and combines results supplied by active content modules. NovaNuke ships providers for News 1.3.0, Pages 1.1.0 and Downloads 1.1.0.

## Installation

1. Update News, Pages and Downloads from `/admin/modules` when they are already installed.
2. Install and enable Search.
3. Optionally add `/search` to a public menu.

Search term logging is disabled by default. Users with `search.manage` can change it at `/admin/search`. The aggregate log stores only the normalized term, count and last-search time; it stores no user or network identifier.

## Provider contract

A module can participate without making Search a required dependency. During `boot()`, listen for the registration hook only when its payload class is available:

```php
$context->events->listen('search.providers.registering', function (object $event): void {
    if ($event instanceof SearchProvidersRegistering) {
        $event->registry->add(new ExampleSearchProvider());
    }
});
```

The provider implements `SearchProviderInterface`:

- `type()` returns a unique lowercase identifier;
- `label()` returns its human-readable filter label;
- `search(SearchQuery $query)` returns `SearchProviderResult`;
- every item is a `SearchResultItem` with a local absolute URL.

Providers must use prepared statements, return only published content, enforce access with `SearchQuery::$userId`, sort newest first and honor the requested limit. Do not return administrator drafts or rely on the result template to hide restricted records.

## Search behavior and limits

- Terms contain 2–100 Unicode characters.
- Results are merged newest-first and paginated in groups of 10.
- A query reads at most 200 results per provider. A type-filtered search therefore exposes at most 20 pages; combined searches can expose more according to their number of active providers.
- MySQL/MariaDB `LIKE` is the initial backend; relevance ranking and stemming are intentionally out of scope.
- Result excerpts are stripped of HTML, shortened and escaped before the matching text is wrapped in `<mark>`.

Because `%term%` queries cannot efficiently use ordinary B-tree indexes, large sites should monitor query time. A future module migration can add MySQL full-text indexes without changing the provider contract.
