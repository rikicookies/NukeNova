# News module

News is an installable NovaNuke module. Copying its directory only makes it available; an authorized administrator must install and enable it from `/admin/modules`.

## Editorial workflow

- `draft`: visible only in administration and available to users with `news.edit`.
- `scheduled`: requires `news.publish` and a future UTC publication time.
- `published`: requires `news.publish`; an omitted publication time becomes the current UTC time.

Scheduled articles become publicly queryable when their publication time arrives without requiring a cron task. Editors without publication permission cannot bypass the workflow by changing the submitted status.

## Content

Articles support categories, topics, comma-separated tags, summaries, sanitized enriched content, an optional featured image path below `/uploads/`, featured status, comment readiness and basic SEO fields. Slugs are unique and form the public URL `/news/{slug}`.

The first release expects images to have already been placed by a trusted upload workflow. The dedicated media uploader will be added separately; arbitrary filesystem paths and external featured-image URLs are rejected.

## Events

Creating an article dispatches `content.created`; editing dispatches `content.updated`. Both receive a `ContentChanged` value containing content type, article ID and actor ID.

The module listens to `admin.menu.building` to add its dashboard entry without modifying the core dashboard for module-specific functionality.

News 1.1.0 also listens to `comments.content.checking`. It accepts only an existing public article whose `comments_enabled` flag is on. If the optional Comments module is disabled, News continues to render normally and shows no comment forms.

## RSS

News 1.2.0 publishes RSS 2.0 at `/news/rss.xml`. It contains at most the 20 newest articles whose publication time has arrived. Drafts, deleted articles and future scheduled articles are never included.

The feed uses the installed site name, URL and locale. URLs are absolute, publication dates use the RSS date format, XML values are created as DOM text nodes, and the response declares `application/rss+xml`, disables MIME sniffing and permits five minutes of public caching. Both bundled themes advertise the feed in the document head.

`site.url` must remain a valid HTTP or HTTPS base URL without credentials, query parameters or fragments. NovaNuke refuses to generate misleading feed links when that setting is unsafe.

## View counts

The detail controller increments a view at most once per article in the current session and retains only the latest 100 IDs. This is a basic counter, not an analytics or identity-tracking system.
