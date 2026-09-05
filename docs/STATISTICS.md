# Statistics module

Statistics 1.0.0 provides a permission-protected dashboard at `/admin/statistics`, an optional public page at `/statistics`, and a disabled-by-default summary block.

## Privacy model

Traffic is stored only as daily aggregate counters. Each row contains:

- calendar date in UTC;
- broad site section such as News, Pages or Downloads;
- referring hostname or the `direct`/`other` bucket;
- general browser family;
- general device family;
- accumulated view count.

The tracker does not store IP addresses, full URLs, query strings, page slugs, complete referrers, user-agent strings, cookies, usernames or per-visitor histories. Authentication, administration, installation and private-message routes are excluded. Only public `GET` requests are counted.

At most 100 distinct referring domains are retained per day; additional domains use `other`. IP-address and localhost referrers also use `other`. Tracking failures are logged and never prevent the requested page from loading.

## Dashboard

The administrative dashboard includes registered and recently active users, published content, comments, downloads, link visits, poll votes, 30-day traffic, sections, referrers, devices, browsers, recent administrative activity and most-viewed content. Optional-module queries safely return zero when their tables do not exist.

Collection is enabled initially. The public page is disabled initially. A user with `statistics.manage` can change both settings; `statistics.view-admin` controls access to the private dashboard.

The module creates a disabled `statistics-summary` block. Enable and position it from `/admin/blocks` when desired.
