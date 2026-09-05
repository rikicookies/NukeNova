# Internationalization

NovaNuke 0.1.0-alpha.18 includes a small JSON-based translator and central locale registry. The installed `site.locale` setting selects the public locale, an authenticated user's profile may override it, and `APP_FALLBACK_LOCALE` selects the fallback. The bundled interface locales are `en` and `es`.

## Core catalogues

Core messages live in:

```text
language/
  en.json
  es.json
```

Each core catalogue must define `locale.native_name`. A correctly named new catalogue is discovered automatically and appears in the installer, general settings and profile preferences; validators use the same registry instead of separate hard-coded lists.

Twig templates translate a key with:

```twig
{{ trans('common.sign_in') }}
{{ trans('home.signed_in', {username: user.username}) }}
```

Twig continues escaping the returned string. Translation catalogues must contain plain interface text, not trusted HTML. Build markup in the template rather than placing HTML in a message.

## Module catalogues

An enabled module may provide:

```text
modules/Example/language/en.json
modules/Example/language/es.json
```

The module slug becomes its namespace automatically:

```twig
{{ trans('example::page.title') }}
```

Missing locale messages fall back to the configured fallback locale. Missing keys render the key itself so omissions are visible during development. A module cannot replace core or another module's catalogue.

## Theme catalogues

The active theme may provide the same `language/en.json` and `language/es.json` files. Its stable Twig namespace is `theme`:

```twig
{{ trans('theme::home.heading') }}
```

Only the active theme catalogue is registered. Switching themes therefore switches its visual interface messages without modifying the core.

## Catalogue rules

- files must be valid UTF-8 JSON objects;
- keys should use lowercase dot notation;
- values must be strings;
- placeholders use `{name}` and accept scalar values only;
- keep user content and secrets out of translation files;
- never concatenate a visitor-controlled value into a translation key.

Malformed catalogues fail safely through NovaNuke error handling and identify the namespace and locale without exposing the invalid file contents.

Run `php bin/cms i18n:check` before packaging. It checks the core and every module/theme language directory, requires English and Spanish catalogues, validates safe key/value shapes and fails when the two catalogues do not contain identical keys.
