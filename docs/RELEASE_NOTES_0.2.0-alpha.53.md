# NovaNuke 0.2.0-alpha.53

## Admin Experience Completion

This is a larger batch release that closes the shared Admin navigation/header consistency pass instead of shipping one screen per alpha.

### Included
- General Settings, User Settings, System Information and Activity Logs use shared Admin breadcrumbs/page headers.
- Menus, Modules and Themes use the same pattern while preserving all existing forms and lifecycle actions.
- Comments, Demo Content, Media, Polls, Private Message Reports, Search and Statistics now share the same Admin navigation language.
- Wiki index, editor, history, comparison and revision inspection screens now use the shared Admin navigation/header pattern.
- Redundant legacy return links were removed where breadcrumbs now provide hierarchy.

### Explicit exclusions
- The Admin dashboard keeps its purpose-built dashboard presentation.
- Blocks is intentionally untouched; the existing project priority remains to avoid Blocks work except for critical regressions.

### Compatibility
No database migration, permission change, route change, module contract change or Composer dependency change is introduced by this release. Existing POST/CSRF and authorization behavior is preserved.

### Upgrade
Run `php bin/cms upgrade:check --from=0.2.0-alpha.52`, the normal unit/integration/release checks, then `php bin/cms upgrade:complete --from=0.2.0-alpha.52`.
