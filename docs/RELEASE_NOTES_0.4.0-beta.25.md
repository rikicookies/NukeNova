# NovaNuke 0.4.0-beta.25

## RC hardening batch

Beta 25 intentionally groups multiple hardening items into one checkpoint rather than shipping a micro-release.

The batch tightens session-cookie scope and `__Host-` rules, production HTTPS CSP, request-path canonicalization, external redirects, ZIP/image upload validation, deployment secret checks and the consolidated RC deployment preflight.

Run the new production-like aggregate with:

```bash
php bin/cms rc:deployment
```

This is intentionally stricter than the normal local-development `check:site`. Production-only requirements and optional hardening recommendations belong in the RC deployment pass, not in everyday Laragon development checks.

Beta 25 also fixes a discovered CLI import regression in the verified file-restore command from Beta 23.

No database migration or visual redesign is included.
