# NovaModern

NovaModern is an optional responsive theme for the public site and Admin. It is inspired by modern application workspaces but is implemented specifically for NovaNuke without copying Limitless code or loading a frontend framework.

## Installation

1. Copy the release files and clear the application cache.
2. Open **Admin → Themes**.
3. Install **NovaModern 1.1.0** (or use Update if 1.0.0 is already installed).
4. Activate it and, if desired, configure its accent color and tagline.

Default and Classic remain available for an immediate rollback.

## Navigation

On desktop, the left navigation switches between its full width and an icon rail. The preference is stored in the browser under `novamodern.navigation`; it is not personal data and is not sent to the server. On smaller screens it becomes an overlay drawer that closes by clicking outside or pressing Escape.

Public navigation renders the existing enabled `primary` menu and continues to respect item roles, hierarchy, active state and new-window settings. The first version uses a safe generic link icon for public menu items because configurable menu icons are intentionally deferred.

Admin navigation is constructed centrally, filtered with `AuthorizationService` and available on every `/admin` route. Modules may continue using the original three arguments or optionally provide icon and group metadata:

```php
$menu->add('News', '/admin/news', 'news.edit', 'newspaper', 'content');
```

Unknown valid groups fall back to **Other modules**. Icon identifiers must use lowercase letters, numbers and hyphens; arbitrary SVG or HTML is never accepted.

## Layout and blocks

Public content has a permanent primary navigation column on desktop. Existing left and right block positions render in their own content columns and collapse to a single column on small screens. Admin does not render public blocks.

Version 1.1.0 narrows desktop column gaps, allows detail content to use the available center width and explicitly overrides the legacy dark component palette with consistent light surfaces, dark text, readable muted text and accessible form controls. Responsive block ordering remains documented separately as a known issue.

NovaModern does not change block providers, storage, visibility or rendering. Remaining dynamic block work stays postponed in `KNOWN_ISSUES.md`.

## Assets

The theme uses plain CSS, a small vanilla JavaScript file and a same-origin SVG sprite. It has no CDN, Bootstrap, jQuery, icon-font or build-step requirement, keeping it suitable for shared hosting.
