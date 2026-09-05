# Blocks

NovaNuke blocks are small, administrator-managed content regions rendered around a page. Administrator-authored blocks support sanitized enriched HTML or Markdown and are intentionally not PHP or Twig editors.

## Content format

Choose **Sanitized HTML** when pasting or authoring allowed HTML elements. Choose **Markdown** for headings, emphasis, lists, quotes, code and links using Markdown syntax. Existing blocks remain HTML until an administrator explicitly changes their format.

Markdown source is stored so it remains editable. At render time NovaNuke uses `league/commonmark` with embedded HTML stripped and unsafe links disabled, then passes the generated HTML through the core sanitizer. Changing a block from HTML to Markdown does not translate its old content automatically; review the source before saving a format change.

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

Allowed output includes paragraphs, headings, lists, emphasis, code, quotes, links and simple containers. NovaNuke removes scripts, styles, embeds, iframes, SVG, event attributes and unapproved attributes. Links accept relative URLs, anchors, HTTP, HTTPS and email only. Links opening a new tab receive `noopener noreferrer`.

Blocks cannot contain PHP, Twig, JavaScript event handlers or executable templates. Trusted installed modules can provide dynamic content through `block.rendering`; the core renders it only when a filesystem module explicitly responds. Administrator-entered HTML remains sanitized and cannot access this hook.
