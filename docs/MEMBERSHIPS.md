# Memberships

NovaNuke memberships are manual access plans built on the Core `vip` entitlement. They do not require a payment provider.

## Built-in plans

- `free` — removes active VIP access.
- `vip-30` — 30 days.
- `vip-90` — 90 days.
- `vip-annual` — 365 days.
- `vip-lifetime` — no expiration.

Assigning a new VIP plan replaces the currently active VIP entitlement and keeps the previous record for history. Revoking a membership marks the current grant revoked instead of deleting historical records.

## Administration

Users with `memberships.manage` can open `/admin/memberships`, search users, filter membership status, assign plans and revoke active VIP access. The per-user membership page displays the historical grants, source, note and granting administrator.

## Content access

Memberships intentionally reuse the existing `vip` audience contract. News, Pages, Downloads, Web Links, Wiki and module audiences that already check VIP do not need a separate membership-specific permission.

## Payments

There is no payment processing in this phase. Payment providers, if added later, should create/revoke the same entitlement records through a dedicated service instead of bypassing MembershipService.

## Membership API contract

Core and module code that only needs to answer membership questions should depend on `MembershipManagerInterface`.

```php
$memberships->isVip($userId);
$memberships->status($userId);
```

`isVip()` is a computed lookup. It does **not** mean NovaNuke stores `is_vip = 1` on the user record. Active access is still derived from entitlement period, expiration and revocation state.

Bundled catalogue queries may use direct SQL for efficient filtering across hundreds of rows, but third-party modules should use the Membership API unless they are implementing a similarly reviewed query-layer optimization.

## Lifecycle events and expiration processing

Membership assignment, revocation and expiration expose Core events so optional modules can react without querying entitlement tables. Expiration itself remains based on `expires_at`; `expired_event_at` is only an idempotency marker that prevents emitting the same expiration event more than once.

Run normal maintenance processing to discover newly expired grants. If Notifications is enabled, users receive membership lifecycle notifications through those events.

## Scheduling and extensions

Administrators can schedule one future VIP plan per user. A future grant cannot overlap a currently active VIP period, and Lifetime VIP cannot have a future overlapping plan. A scheduled grant may be cancelled before its start time.

Extending an active finite VIP membership adds days to its existing `expires_at`, preserving unused time. The extension does not restart the period from the current clock time and does not create a user-level VIP boolean.

## Operational presentation

Account settings expose both current membership and any scheduled future VIP grant. Admin detail additionally reports approximate days remaining for finite VIP and approximate days until scheduled activation.

The admin screen provides +30, +90 and +365 day shortcuts as conveniences over the same additive extension operation; unused time is preserved.

Scheduling and schedule cancellation emit `membership.scheduled` and `membership.schedule_cancelled` Core events so optional Notifications can inform the affected account.

## Scheduled activation

A scheduled VIP grant becomes effective automatically from `starts_at`; no boolean activation flag controls access. Maintenance emits `membership.activated` once when a scheduled grant has actually entered its active period. `activated_event_at` is only an idempotency marker for that event. Existing grants are pre-marked when the marker migration is installed so upgrades do not create retroactive activation notifications.

Revoking the current VIP does not implicitly cancel a separate future schedule. Administrators use the explicit scheduled-cancellation operation for that. Assigning Free intentionally clears both current VIP and a pending future VIP so the resulting account state is unambiguous.

## Validation

For a focused Beta 2 regression pass, run `composer test:membership`. The runner uses the normal isolated MySQL integration-test environment and destroys each temporary database after its test.

For an existing installation, run `php bin/cms membership:check`. The command is read-only and reports schema or grant-integrity problems such as duplicate active/scheduled grants or active/future overlap.
