# NovaNuke 0.4.0-beta.12

## Beta 2 — QA stabilization

This release rewrites the extension identity contract test explicitly. The test now requires that `EntitlementService::extend()` does **not** rewrite the current plan to `vip-custom`.

No runtime behavior changes.
No database migration.
