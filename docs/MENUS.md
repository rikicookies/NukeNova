# Menus

NovaNuke stores navigation separately from themes. Administrators can create several menus, while themes render a menu by its slug. Both bundled themes render `primary` in their header.

## Link types

- `internal`: a local path beginning with one slash, such as `/pages/about`.
- `module`: a module slug such as `welcome`, resolved to `/welcome`.
- `external`: an absolute HTTP or HTTPS URL.

External schemes such as JavaScript, data and file URLs are rejected. Opening a supported external link in a new tab automatically adds `noopener noreferrer`.

## Hierarchy and visibility

Items can use another item from the same menu as their parent. NovaNuke rejects self-parenting and descendant cycles. Deleting a parent deletes its children after explicit confirmation.

Every item has an order, enabled state and optional role restrictions. No selected roles means everyone. If a parent is disabled or unavailable to the current visitor, its descendants are not promoted into the menu.

## Theme integration

The `menus` Twig global is keyed by menu slug. For example, `menus.primary` is an already filtered and ordered tree. Themes can override `menus/navigation.twig` to change the markup without changing the menu data or core.

Menu item titles and URLs remain escaped by Twig. Menu management never accepts PHP, Twig or JavaScript.
