# SEO and sitemap

Install and enable the optional SEO module from `/admin/modules`. It provides:

- `/sitemap.xml`, cached by browsers and proxies for 15 minutes;
- `/robots.txt`, with public crawling allowed and private/administrative routes disallowed;
- the `sitemap.collecting` extension event.

The configured Site URL in `/admin/settings` must be the exact public HTTP or HTTPS origin, including a subdirectory when applicable. NovaNuke rejects unsafe base URLs and never accepts browser input when generating sitemap locations.

News 1.4.0 contributes its index and every currently published article. Pages 1.2.0 contributes only public pages; member- and role-restricted pages are deliberately excluded. Drafts, future scheduled content and soft-deleted records are never listed.

## Content metadata

News and Page detail templates render:

- a canonical URL;
- SEO title and description with sensible content fallbacks;
- Open Graph title, description, URL, type and optional image;
- Twitter summary card metadata;
- publication and modification dates for news articles.

Restricted pages emit `noindex,nofollow`. Metadata values remain escaped by Twig. Featured image fields continue accepting only validated paths below `/uploads/`.

## Adding module URLs

A module declares `sitemap.collecting` in `module.json` and registers a listener:

```php
$context->events->listen('sitemap.collecting', static function (object $event): void {
    if (! $event instanceof \Modules\Seo\src\SitemapCollecting) return;
    $event->add('/catalog/example', '2026-09-03 12:00:00', 'weekly', 0.7);
});
```

Only clean internal paths are accepted. Query strings, protocol-relative URLs, whitespace and control characters are rejected. Providers must enforce publication and access rules before adding a URL. The collection deduplicates paths and enforces the standard 50,000 URL limit.
