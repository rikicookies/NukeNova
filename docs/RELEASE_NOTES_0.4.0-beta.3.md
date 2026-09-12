# NovaNuke 0.4.0-beta.3

## Beta 2 — Memberships, batch 3

This batch closes the main membership boundary so callers ask the Membership API about access rather than reaching into entitlement persistence.

- PublicProfileController uses MembershipManagerInterface.
- AccessAudience uses MembershipManagerInterface for `vip`.
- UsersController uses MembershipManagerInterface for current state, plan assignment, custom-day legacy grants and revocation.
- Downloads, Pages, Web Links and Wiki use `isVip()` for per-item access checks.
- Public profiles show the active membership plan name.
- No `users.is_vip` or equivalent persisted boolean flag is introduced.
- `grantDays()` preserves the earlier arbitrary-duration administrator workflow without exposing EntitlementService to the controller.

Performance-oriented catalogue queries may still use SQL EXISTS against `user_entitlements`; this is an internal bundled-module optimization, not the extension contract for new modules.

No payment processing is included.
