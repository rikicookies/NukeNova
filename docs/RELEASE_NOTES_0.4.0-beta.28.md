# NovaNuke 0.4.0-beta.28

## Final beta — complete bundled-module visual review

Beta 28 is the planned final beta before `0.4.0-rc.1`.

This release explicitly reviews every bundled module rather than only the modules touched by recent feature work. Polls and Statistics received the largest refresh because their public/admin surfaces still reflected the earlier presentation line. Friends, Notifications, Quotes, Private Messages, Search, Media, Comments, Demo Content, Welcome and the WebLinks submission flow were also normalized.

Previously modernized Downloads, News, Pages, WebLinks and Wiki were re-reviewed. Specialized article, landing-page and Wiki layouts remain specialized where that improves the module rather than forcing every screen into identical markup.

### Cross-theme consistency

The shared module primitives are defined in `public/assets/css/app.css`. NovaModern and Classic receive explicit palette overrides so new module cards, forms and empty states remain native to each theme instead of inheriting dark default colors.

### Audit coverage

`docs/VISUAL_AUDIT_BETA28.md` records all bundled modules and the disposition of their visual review. `BundledModuleVisualConsistencyTest` also verifies that every bundled module is represented in that audit.

### RC boundary

No database migration or major feature is introduced. After Beta 28 passes automated and manual visual acceptance, the next planned release is `0.4.0-rc.1`.
