# NovaNuke 0.3.0-beta.1

Beta 1 starts the production-hardening phase. It intentionally avoids feature work and focuses on deployment safety.

## Hardening included

- Session cookies keep strict-mode, cookie-only and HttpOnly behavior while adding configurable absolute lifetime, idle timeout and periodic session-ID rotation.
- `SESSION_SAME_SITE` is validated; `None` requires `SESSION_SECURE=true`.
- Admin, account, authentication and error responses default to `Cache-Control: no-store, private` unless a route explicitly supplies a cache policy.
- Browser defaults add Cross-Origin-Opener-Policy, Cross-Origin-Resource-Policy and X-Permitted-Cross-Domain-Policies.
- Apache shared-hosting rules block TRACE and harden the public upload directory against executable/script-like files.
- `production:check` now validates SameSite, idle timeout, session-ID rotation and the shipped Apache guard files.

## Upgrade

Direct upgrade is supported from `0.2.0-alpha.60` to `0.3.0-beta.1`. No database migration is introduced by this release.
