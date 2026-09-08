# Content formats

NovaNuke modules should store editable source and an explicit `content_format` value of `html` or `markdown`. They must render that source through `ContentRendererInterface`; controllers must never mark database content as trusted Twig markup before this conversion.

HTML is sanitized at render time. Markdown is converted with embedded HTML disabled and unsafe links rejected, then sanitized. Modules choose a `ContentProfile` according to the field: `FullContent`, `Description`, `Comment`, `Profile` or `Message`. Profiles establish a stable contract for progressively narrower allowlists.

Module migrations own their format columns. Existing enriched content should migrate to `html` unless the module can prove another source format. Modules must preserve the original source so editors can switch or revise formats without editing generated HTML.
