# NovaNuke 0.4.0-beta.6

## Beta 2 — Memberships, batch 6

This batch focuses on operational visibility and admin usability before the accumulated Beta 2 validation pass.

- explicit membership presentation state
- account visibility for scheduled VIP
- approximate days remaining for finite VIP
- approximate days until scheduled activation
- quick +30/+90/+365 day extension actions
- Scheduled VIP dashboard metric
- `membership.scheduled`
- `membership.schedule_cancelled`
- optional user notifications for scheduling/cancellation
- membership history distinguishes Scheduled/Active/Expired/Revoked

No new database migration is required.
No payment processing is included.
No persisted `is_vip` / `vip_active` user flag is introduced.
