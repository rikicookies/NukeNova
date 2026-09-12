# NovaNuke 0.4.0-beta.5

## Beta 2 — Memberships, batch 5

This batch adds operational scheduling and time extension.

- schedule VIP 30/90/Annual/Lifetime for a future UTC start
- only one future VIP grant may be pending per user
- a scheduled grant may not overlap an active VIP period
- Lifetime VIP cannot be overlapped
- scheduled grants can be cancelled before they start
- extending VIP adds days from the current expiration instead of resetting from today
- plan identity is retained when time is extended
- admin overview distinguishes Scheduled from Expired/Revoked
- status resolution prioritizes current active membership before future/history records
- no payment processing
- no `users.is_vip` boolean

No new database migration is required in this batch.
