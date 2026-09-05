# Administrative dashboard

NovaNuke 0.1.0-alpha.2 replaces the placeholder dashboard with a permission-aware site overview at `/admin`.

## Data shown

- registered and active users;
- published News, Pages and Downloads totals when those modules are enabled;
- pending Comments moderation count;
- recent editable content across enabled modules;
- recent users and activity-log events;
- detected/enabled module totals and module issues;
- production configuration warnings from the protected system inspector.

Every section is checked against server-side authorization. Hiding a dashboard card is only a usability measure; each destination controller continues enforcing its own permission.

Optional module tables are queried only when the module is enabled and its table exists. Disabling a module preserves its data without leaving stale content on the dashboard.

The dashboard intentionally uses small indexed queries and fixed limits rather than loading complete content histories. It does not add new tracking or collect visitor data.

## Permissions

- `users.view`: user totals and recent users;
- `news.edit`, `pages.edit`, `downloads.manage`: respective content totals and recent items;
- `comments.moderate`: pending comment total;
- `logs.view`: recent administrative activity;
- `modules.manage`: module health summary;
- `settings.manage`: configuration warnings;
- each core/module permission: matching navigation link.

Modules continue adding their administration links through `admin.menu.building`.
