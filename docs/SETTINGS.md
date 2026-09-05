# General settings

NovaNuke 0.1.0-alpha.3 provides the protected `/admin/settings` screen to users with the `settings.manage` permission. Every update requires a valid CSRF token, uses a database transaction and writes an activity event containing changed field names, not their values.

## Public settings

- site name and plain-text description;
- public HTTP or HTTPS URL;
- administrator email;
- English or Spanish interface locale;
- PHP timezone and an approved display date format;
- 5–100 items per page;
- welcome page or an enabled News, Pages or Downloads module as the homepage;
- maintenance mode.

The public URL cannot contain credentials, query parameters, fragments, control characters or a non-HTTP protocol. A module can only be selected as homepage while it is enabled. If it is later disabled, `/` safely falls back to the NovaNuke welcome page.

The shared pagination value is consumed by News, Downloads, Web Links and Search. Installed timezone, locale, description and date format are exposed to Twig as escaped globals. Themes should use `cms_locale`, `cms_description`, `cms_timezone` and `cms_date_format` rather than hard-coded equivalents.

## Deployment settings and secrets

Database credentials, `APP_KEY`, SMTP credentials, session security, debug mode and security-header deployment switches remain in `.env`. They are deliberately not accepted by the administration panel and must never be committed.

The installer writes matching initial `APP_URL`, timezone and locale deployment values plus editable installed-site values. Later public URL changes affect generated registration, verification, password recovery and feed links. Reverse-proxy, TLS, cookie and web-server configuration must still be adjusted by the operator when the actual deployment URL changes.

## Maintenance behavior

Maintenance mode returns HTTP 503 with `Retry-After` and `Cache-Control: no-store` to visitors. Administrative routes and signed-in Super Administrators remain accessible so the site can be restored. Always verify access with a separate browser before enabling it remotely.
