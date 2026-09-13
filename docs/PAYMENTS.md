# Optional payments

NovaNuke Beta 3 introduces a provider-neutral payment boundary. Payments remain optional: Core ships with an empty `PaymentProviderRegistry`, no checkout route, no payment credentials, and no bundled Stripe/PayPal/etc. provider.

## Trust boundary

Core does not accept a browser-supplied `paid=true`, plan grant, or raw payment status. A payment module implements `PaymentProviderInterface` and must authenticate the provider payload/signature before returning a `VerifiedPayment`.

Only a verified payment is passed to `MembershipPaymentProvisioner`.

## Membership provisioning

Payment code does not write `user_entitlements` directly. `MembershipPaymentProvisioner` calls `MembershipProvisionerInterface`, which is implemented by the existing Membership service. This preserves the same VIP plan rules and lifecycle events used by manual administration.

Paid grants use entitlement source `payment`; the provider/reference is stored as the provisioning note for traceability.

## Idempotency

`payment_receipts` stores the minimum normalized receipt required to prevent duplicate fulfillment:

- provider
- external reference
- user
- plan
- amount in minor currency units
- three-letter currency
- processed timestamp

`(provider, external_reference)` is unique. Re-delivery of the same verified event is a no-op. Reusing the same reference with different user, plan, amount, or currency is rejected.

The receipt and membership grant share one database transaction. If membership provisioning fails, the receipt is rolled back so a legitimate retry can succeed later.

## Sensitive payment data

NovaNuke does not store card numbers, CVV/CVC, bank credentials, or provider secrets in `payment_receipts`. Provider modules are responsible for their own credential configuration and signature verification. Prefer hosted/provider-controlled checkout surfaces so NovaNuke never handles raw card data.

## Provider modules

A provider module can register its implementation during module boot:

```php
$context->container
    ->get(\NovaNuke\Core\Billing\PaymentProviderRegistry::class)
    ->register($provider);
```

The provider is responsible for mapping an authenticated provider transaction/product to the correct NovaNuke user and membership plan before constructing `VerifiedPayment`.

## Current Beta 3 boundary

This checkpoint is the payment foundation, not a bundled storefront:

- no provider is enabled by default;
- no public checkout route exists;
- no prices are imposed on the built-in Membership plan catalogue;
- manual Membership administration continues to work exactly as before.

A future provider module can add checkout/webhook UI without changing the Membership entitlement model.


## Operational health check

Run:

```bash
php bin/cms payment:check
```

The command is read-only. It checks the receipt table/columns, user ownership, recognized Membership plan keys, provider-reference uniqueness, and reports which payment providers are currently registered.


## Concurrent duplicate delivery

The database UNIQUE constraint remains the final idempotency boundary. If two matching provider deliveries race, the losing insert is converted from MySQL duplicate-key error `1062` into a domain duplicate, the canonical receipt is reloaded and compared, and the second delivery returns as already processed.

Only duplicate-key errors are translated. Foreign-key, connectivity, schema and other database errors continue to fail normally.
