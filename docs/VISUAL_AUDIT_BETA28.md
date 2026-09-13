# Beta 28 bundled-module visual audit

Beta 28 is the final visual consistency pass before NovaNuke 0.4.0-rc.1. The goal is not to redesign working modules or expand scope. It is to make every bundled module feel like part of the same CMS while preserving its existing behavior.

| Module | Beta 28 review |
| --- | --- |
| Comments | Public thread and moderation surfaces reviewed; standardized empty moderation/report states and shared action styling. |
| DemoContent | Reworked status, coverage and installation presentation into shared system cards/metrics. |
| Downloads | Previously modernized; re-reviewed against the shared page/card/table/form primitives and retained specialized catalog/detail UI. |
| Friends | Reworked group sections, user cards, actions and empty states; message action exposed for accepted friends. |
| Media | Reworked upload panel, metadata cards, media-grid presentation and empty library state. |
| News | Previously modernized; re-reviewed and retained specialized article/list/editor presentation. |
| Notifications | Reworked header, unread treatment, action hierarchy and empty state. |
| Pages | Previously modernized; re-reviewed directory/default/landing/admin surfaces; specialized landing layout retained. |
| Polls | Full public/admin polish: cards, result presentation, voting surface, status badges, inventory table and empty states. |
| PrivateMessages | Full inbox/sent/compose/conversation/blocked/admin polish with shared cards, forms, actions and empty states. |
| Quotes | Reworked public cards, admin form, status badges and empty states. |
| Search | Reworked search form/results/empty states and admin popular-term empty state. |
| Seo | No standalone visual templates; module is provider/service oriented. Admin-facing behavior reviewed through existing system integration. |
| Statistics | Full public/admin polish: metric cards, privacy copy, breakdown panels, tables, activity and empty states. |
| WebLinks | Previously modernized; submission form brought into the current page/header/form system; detail/catalog retained. |
| Welcome | Simplified into shared page-header/action presentation while preserving translation content. |
| Wiki | Previously modernized and intentionally specialized; sitemap/search/index empty states aligned without disturbing Markdown/navigation workflows. |

## Cross-theme review

The shared primitives live in `public/assets/css/app.css`. Beta 28 adds explicit NovaModern and Classic overrides so module cards/forms/empty states do not inherit the default dark palette inside light themes.

Review dimensions:

- page headers and breadcrumbs;
- spacing and section grouping;
- cards and metric grids;
- admin tables and action cells;
- form hierarchy and destructive actions;
- empty states;
- responsive two-column layouts;
- light/dark contrast across bundled themes;
- public/admin presentation consistency.

Functional behavior, routes, permissions, storage and schemas are intentionally outside this visual pass unless a regression is discovered while testing.
