# Production deployment

NovaNuke 0.1.0 is the first stable release. Test a complete backup and restore before every deployment or update.

## Required production environment

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
SESSION_SECURE=true
SESSION_SAME_SITE=Lax
SECURITY_HEADERS_ENABLED=true
SECURITY_HSTS_ENABLED=false
```

Enable HSTS only after HTTPS works correctly on the production domain and every required subdomain. HSTS can make an incorrectly configured site inaccessible until the browser policy expires.

The `log` mailer is development-only. Configure and test encrypted SMTP before enabling registration, email verification or password recovery in production. See `docs/MAIL.md`.

## Files and permissions

- Point the web document root to `public/`, never to the project root.
- Keep `.env`, `vendor/`, `storage/private/`, migrations and source code outside public access.
- Give the web/PHP user write access only where needed: `storage/cache`, `storage/logs`, `storage/sessions`, `storage/private` and published theme assets.
- Do not use world-writable permissions such as `0777` unless a host leaves no safer option, and then resolve it with the host.
- Keep `storage/installed.lock` after every update.

## Apache and Laragon

Set the virtual host document root to `/path/to/novanuke/public` and allow overrides for that directory so `public/.htaccess` can route friendly URLs. Laragon normally creates this mapping automatically when the project directory is configured as a site; verify its document root rather than assuming it.

## Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name example.com;
    root /path/to/novanuke/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~* ^/uploads/.*\.(php[0-9]?|phtml|phar|cgi|pl|py|sh)$ {
        deny all;
    }

    location ~ /\. { deny all; }
}
```

Adjust the PHP-FPM socket to the installed PHP version. TLS certificate setup is server-specific.

## Traditional shared hosting / Bluehost

Prefer a domain or subdomain whose document root can be assigned directly to `novanuke/public`. Keep the rest of NovaNuke one level outside the public document directory. Do not copy only `public/index.php` elsewhere: it resolves the application root relative to its original directory layout.

If the hosting plan cannot point a domain at `public/`, ask the host to change the document root before deployment. Exposing the entire project to compensate is unsafe.

## Release procedure

1. Put the site in maintenance mode from `/admin/settings`.
2. Create database and file backups with `backup:database` and `backup:files`, then move encrypted copies off-server.
3. Preserve `.env`, `composer.lock`, `storage/installed.lock` and all of `storage/private/`.
4. Replace application files and run `composer install --no-dev --optimize-autoloader`.
5. Run `php bin/cms migrate:status`, then `php bin/cms migrate`.
6. Apply module updates from `/admin/modules` and run `php bin/cms migrate:status` again.
7. Clear generated caches if instructed by the release notes.
8. Run `php bin/cms release:check`.
9. Run `php bin/cms security:audit` and correct every failed authorization check.
10. Run `php bin/cms production:check` and correct every required failure.
11. Visit `/admin/system`, resolve warnings and smoke-test authentication, permissions, uploads and module routes.

## Maintenance and cache

Maintenance mode is controlled from `/admin/settings`. Public requests receive HTTP 503 with `Retry-After` and `no-store`; login, password recovery, health checks and administrative routes remain reachable. A signed-in Super Administrator can preview public pages while maintenance is active.

After deploying changed PHP or Twig files, clear generated caches:

```bash
php bin/cms cache:status
php bin/cms cache:clear
```

The clear command is restricted to `storage/cache`, preserves the cache root and resets OPcache when PHP permits it.

Run the data-retention command regularly after first checking its dry-run output. See `docs/MAINTENANCE.md` for Laragon, cron and shared-hosting examples.

For local download storage, run `php bin/cms downloads:orphans` after backups. Use `--delete` only after reviewing the eligible count; new files receive a 24-hour grace period.
