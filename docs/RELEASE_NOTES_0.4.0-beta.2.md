# NovaNuke 0.4.0-beta.2

## Beta 2 — Memberships, batch 2

This batch formalizes membership state as a Core contract rather than exposing entitlement persistence to account-facing code.

### Changes

- `MembershipManagerInterface`
- explicit Free membership state
- named VIP plan status in account settings
- lifetime/expiration display
- admin dashboard cards for active and lifetime VIP memberships
- expiring-within-seven-days administrative attention item
- membership quick action
- existing VIP audience compatibility remains unchanged

Payments remain intentionally out of scope.
