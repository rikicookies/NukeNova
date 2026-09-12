# NovaNuke 0.4.0-beta.4

## Beta 2 — Memberships, batch 4

This batch adds operational membership lifecycle events.

- `membership.assigned`
- `membership.revoked`
- `membership.expired`
- Core payload DTOs for each lifecycle event
- expiration detection integrated with maintenance processing
- one-time expiration event delivery through `expired_event_at`
- optional Notifications integration
- no `users.is_vip` flag
- no payment processing

Core migration:
- `2026_09_10_000020_add_membership_expiration_event_marker.php`
