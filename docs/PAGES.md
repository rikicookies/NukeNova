# Pages module

Pages provides static and informational content without adding page-specific behavior to the core. Install and enable it from `/admin/modules`, then manage content at `/admin/pages`.

## Content and publication

Pages support drafts, scheduled publication and immediate publication. `pages.edit` permits draft preparation; `pages.publish` is required for scheduled or published states. Slugs are unique inside the module and produce `/pages/{slug}` URLs.

The editor accepts sanitized enriched HTML, safe image paths below `/uploads/`, SEO title and description, an optional parent page, and either the `default` or `landing` template. A theme can override these files under `module-templates/pages/`.

## Access

Each page has one audience:

- `public`: available to everyone;
- `members`: requires an active signed-in account;
- `roles`: requires at least one of the selected roles.

Access is enforced by the controller and repository, not merely hidden in the template. Unauthorized guests are sent to login; signed-in users without an allowed role receive HTTP 403.

## Hierarchy and navigation

A page can have one parent. The repository rejects missing parents, self-parenting and indirect cycles. The public page shows its parent as a breadcrumb.

`Show in page directory` adds the page to `/pages` for viewers who have access. To place a page in a theme menu, open `/admin/menus`, create an internal menu item, and use `/pages/the-page-slug` as its target. Role restrictions on the page remain enforced even if a menu item is visible.

## Comments

Comments are optional per page. Pages validates through `comments.content.checking` that the requested page is published, has comments enabled and is accessible to the current viewer. Disabling Comments leaves Pages operational and preserves all comment data.

Immediately before selecting the template, Pages dispatches `page.rendering` with a `PageRendering` value. Trusted modules may enrich its page data without editing Pages or the core. The template name is revalidated after listeners run.
