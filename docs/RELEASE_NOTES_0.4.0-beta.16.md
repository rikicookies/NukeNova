# NovaNuke 0.4.0-beta.16

## Beta 3 — Optional payments foundation

Beta 16 starts the optional-payments roadmap without turning NovaNuke into a payment-dependent CMS.

Core now exposes a provider-neutral verification and fulfillment boundary. No payment provider is bundled and the registry is empty by default. A provider module must authenticate its provider payload before returning a normalized `VerifiedPayment`; Core never accepts a browser-supplied paid flag.

Verified payments are fulfilled through `MembershipProvisionerInterface`, preserving the existing Membership plan and lifecycle model. `payment_receipts` provides durable idempotency so repeated webhook delivery cannot grant the same payment twice. Conflicting reuse of an existing provider/reference is rejected.

Receipt insertion and Membership provisioning share one database transaction. A failed grant rolls the receipt back and remains retryable.

### Database migration

Run the normal migration flow. Beta 16 adds:

`2026_09_10_000022_create_payment_receipts.php`

The table stores normalized receipt metadata only. It does not store card numbers, CVV/CVC, bank credentials or provider secrets.

### Deliberately not included

- Stripe, PayPal or another bundled provider
- public checkout
- Core webhook endpoint
- plan prices
- recurring billing
- automatic cancellation/refunds

Manual VIP/Membership administration remains the default behavior.

### Validation

```bash
composer test:checkpoint
composer check:release
```
