# NovaNuke 0.4.0-beta.9

## Beta 2 — Memberships validation harness

This release adds no new end-user Membership features. It packages the accumulated Beta 2 validation workflow.

### Focused validation

```bash
composer test:membership
```

This loads `.env.testing`, enables the isolated MySQL integration harness, creates disposable NovaNuke databases, runs Membership-related unit/integration tests and destroys those databases afterward.

Covered lifecycle:
- Free baseline
- VIP 30 assignment
- additive extension
- revoke
- Lifetime
- future scheduling
- duplicate schedule rejection
- scheduled cancellation
- scheduled activation event
- expiration event
- activation/expiration event idempotency

### Real installation audit

```bash
php bin/cms membership:check
```

This is read-only and checks:
- required membership columns
- absence of `users.is_vip` / `vip_active`
- known plan keys
- one active VIP grant per user
- one future VIP grant per user
- no active/future overlap
- entitlement ownership

No database migration is required.
No payment processing is included.
