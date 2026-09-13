# NovaNuke 0.4.0-beta.17

## Beta 3 — Optional payments stabilization checkpoint

Beta 17 closes the provider-neutral Optional Payments foundation.

### Concurrent idempotency

Beta 16 already rejected ordinary duplicate fulfillment. Beta 17 also handles the race where two verified provider deliveries with the same `(provider, external_reference)` arrive concurrently. A MySQL duplicate-key result is translated to the payment domain, the canonical receipt is reloaded and compared, and a matching delivery returns as an idempotent duplicate instead of surfacing a raw PDO error.

Conflicting reuse of the same reference still fails.

### Operations

Run:

```bash
php bin/cms payment:check
```

The audit is read-only and validates:

- `payment_receipts` table and required columns
- receipt ownership
- recognized Membership plan keys
- provider/reference uniqueness
- currently registered payment providers

`composer check:release` now includes this payment audit.

### Optional by design

NovaNuke still ships with no payment provider and no Core checkout/webhook route. Manual Membership administration remains fully supported and no payment system is required to use the CMS.

No new database migration is introduced by Beta 17. Beta 16's `payment_receipts` migration remains the only Beta 3 payment schema addition.

### Validation

```bash
composer test:checkpoint
composer check:release
```
