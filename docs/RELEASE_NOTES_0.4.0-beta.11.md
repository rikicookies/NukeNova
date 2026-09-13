# NovaNuke 0.4.0-beta.11

## Beta 2 — QA stabilization

This release fixes the final stale assertion discovered by `composer test:membership`.

`EntitlementService::extend()` already behaved correctly: it updates expiration metadata without changing the current membership plan identity. The test now asserts that `plan_key` is **not** rewritten to `vip-custom`.

No runtime Membership behavior changes are included.
No database migration is required.
