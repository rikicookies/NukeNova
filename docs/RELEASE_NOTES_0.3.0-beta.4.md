# NovaNuke 0.3.0-beta.4

## Production hardening — request abuse, CSRF, throttling and error disclosure

- Request capture rejects excessive top-level GET/POST parameter counts.
- Nested request arrays are capped at a safe depth before application dispatch.
- Production exception responses expose only a reference identifier.
- Production logs redact sensitive values and normalize project paths to `[APP]/...`.
- Login, registration, resend, account password, account deletion and account email throttles use HTTP 429 with `Retry-After` where the flow explicitly blocks further attempts.
- Core/admin POST coverage has regression tests for CSRF-protected controllers and obvious state-changing GET routes.

No database migration is required.
