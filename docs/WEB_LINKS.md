# Web Links module

Web Links 1.0.0 provides a curated external-link directory at `/links` and administration at `/admin/web-links`.

## Features

- categories, descriptions and optional images;
- pending, published and rejected moderation states;
- featured links;
- newest, popular and alphabetical ordering;
- title and description search using MySQL/MariaDB;
- authenticated user submissions, always pending initially;
- protected visit counters;
- broken-link reports and an administrative queue.

Users can submit at most five links per hour. Visitors can submit at most five broken-link reports per hour. The same identity counts once per link within 24 hours, and duplicate reports for a link are rejected.

## URL security

Only absolute HTTP and HTTPS URLs are accepted. URLs containing credentials, control characters, dangerous schemes such as `javascript:`, `data:` or `file:`, and malformed addresses are rejected. NovaNuke does not fetch submitted URLs on the server, avoiding a server-side request-forgery surface. Public visits pass through a controller, recheck publication state, record a privacy-preserving count and use a validated external redirect with `Referrer-Policy: no-referrer`.

Descriptions use the shared enriched-HTML sanitizer. Image values must be safe paths below `/uploads/`; this module does not download remote images.

Install and enable the module from `/admin/modules`. Grant `web-links.manage` only to roles allowed to approve submissions and manage reports.
