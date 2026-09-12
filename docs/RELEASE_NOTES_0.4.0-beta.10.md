# NovaNuke 0.4.0-beta.10

## Beta 2 — QA stabilization

The focused Membership validation exposed stale contract assertions rather than three runtime Membership defects.

1. `AccessAudience` correctly depends on `MembershipManagerInterface`; the old test still expected `EntitlementService::VIP`.
2. Immediate activation markers exist on the two creation paths (`grant` and `replace`). The old count of three predated the Beta 8 extension hardening that removed extension-as-creation.
3. The canonical Beta 9 source already asserts that extension must not rewrite `plan_key` to `vip-custom`. A local test expecting the opposite indicates source/test drift.

No database migration is required.
No runtime Membership behavior changes are included.
