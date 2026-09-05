# Blocks

NovaNuke blocks are small, administrator-managed content regions rendered around a page. The first implementation supports sanitized enriched HTML and is intentionally not a PHP or Twig editor.

## Positions

- `header`
- `left-sidebar`
- `right-sidebar`
- `before-content`
- `after-content`
- `footer`

Themes render positions through `blocks/region.twig`. Sidebar positions appear when a page uses the theme's `two-sidebars.twig` layout. A theme may change the markup, but it should retain automatic escaping for the title. Block HTML is sanitized before storage and exposed as trusted markup only after that process.

## Visibility

Blocks can be enabled or disabled, scheduled in UTC, limited to selected roles, restricted to module slugs and filtered by page patterns. A pattern can be an exact path such as `/welcome` or a subtree such as `/news/*`.

No selected role means all visitors. Guests use the `guest` role. Administrative pages intentionally never render public blocks.

## Security

Allowed HTML includes paragraphs, headings, lists, emphasis, code, quotes, links and simple containers. NovaNuke removes scripts, styles, embeds, iframes, SVG, event attributes and unapproved attributes. Links accept relative URLs, anchors, HTTP, HTTPS and email only. Links opening a new tab receive `noopener noreferrer`.

Blocks cannot contain PHP, Twig, JavaScript event handlers or executable templates. Module-provided dynamic block types will use registered server-side providers in a later phase rather than code entered in the administration panel.
