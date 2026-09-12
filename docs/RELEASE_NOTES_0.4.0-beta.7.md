# NovaNuke 0.4.0-beta.7

## Beta 2 accumulated stabilization

This release is the first stabilization pass over the six Memberships batches rather than another feature batch.

Fixed issues found by accumulated review:
- Free admin rendering used generic `active` instead of the VIP state.
- extension controls were visible outside finite active VIP.
- AccountLifecycleService/DataPruner dependency wiring had been crossed during the earlier maintenance integration.
- one immediate entitlement insert had an activation-marker column/value mismatch.
- revoking current VIP could also revoke a future scheduled grant.

Scheduled activation:
- new `membership.activated` Core event
- new `MembershipActivated` payload
- maintenance discovers scheduled grants when they actually become active
- `activated_event_at` prevents duplicate activation events
- migration pre-marks already-started historical grants so upgrading does not generate retroactive notifications
- optional Notifications integration informs the user when scheduled VIP truly becomes active

Core migration:
- `2026_09_10_000021_add_membership_activation_event_marker.php`

No persisted `users.is_vip` or `vip_active` flag is introduced.
No payment processing is included.
