# NN-MEM-01

Membership access remains derived directly from `starts_at`, `expires_at` and `revoked_at`; maintenance events never control whether a user is VIP.

NN-MEM-01 hardens lifecycle notification processing. For every eligible scheduled activation or expiration, maintenance opens a transaction, locks and revalidates the grant, dispatches the lifecycle event, and records the idempotency marker only after listener success. Concurrent workers revalidate after acquiring the lock and skip an event already completed by another worker. A listener exception rolls the transaction back and leaves the grant eligible for a later retry.

No migration, plan, permission, payment or user-interface change is introduced.
