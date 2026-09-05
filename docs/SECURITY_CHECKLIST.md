# Production security checklist

- [ ] PHP 8.3+ and all required extensions are supported and patched.
- [ ] The document root is exactly `public/`.
- [ ] `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] `APP_URL` is the canonical HTTPS URL.
- [ ] `SESSION_SECURE=true`; session cookies remain HTTP-only and SameSite.
- [ ] Security headers are enabled; HSTS is enabled only after HTTPS validation.
- [ ] `.env`, backups, private downloads and logs cannot be requested over HTTP.
- [ ] Database credentials use the minimum privileges required by NovaNuke.
- [ ] The first administrator uses a unique password and unused accounts are suspended.
- [ ] Administrative permissions are reviewed role by role.
- [ ] Upload limits and allowed MIME/extension pairs are intentionally configured.
- [ ] A real mail transport is configured and recovery links are tested before launch.
- [ ] Database and file backups are encrypted, off-server and restore-tested.
- [ ] Detailed PHP errors are disabled at the server level as well as in NovaNuke.
- [ ] `/admin/system` contains no unresolved production warnings.
- [ ] The authorization audit reports an active Super Administrator, complete core permissions and no administrative grants on Guest or Member.
- [ ] Maintenance mode was tested without losing login, recovery or administrative access.
- [ ] Generated caches were cleared after deployment.
