# NovaNuke 0.4.0-beta.1

## Beta 2 — Memberships, batch 1

NovaNuke now exposes the existing VIP entitlement foundation as a first-class manual membership system.

Plans:
- Free
- VIP 30 days
- VIP 90 days
- VIP Annual
- VIP Lifetime

Administration:
- `/admin/memberships`
- active, expiring, lifetime, expired/revoked, never-VIP and all-user filters
- username/email search
- replace an active plan atomically
- revoke VIP immediately
- optional administrative notes
- per-user membership history
- activity-log records for assignment and revocation

Authorization:
- new `memberships.manage` permission
- assigned to Super Administrator by default
- all state changes remain POST + CSRF protected

Compatibility:
- existing `vip` audience behavior is unchanged
- existing VIP grants are retained and represented as `vip-custom` through the metadata migration introduced before this release
- no payments or checkout are included

Core migrations:
- `2026_09_10_000019_add_membership_permission.php`
