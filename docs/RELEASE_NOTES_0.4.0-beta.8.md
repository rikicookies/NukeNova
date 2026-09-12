# NovaNuke 0.4.0-beta.8

## Beta 2 — Memberships stabilization pass 2

This release contains no new membership features. It fixes inconsistencies discovered by auditing the accumulated Beta 2 code as a whole.

- fixed unbalanced Twig flow in `/admin/memberships/{id}`
- Free renders as Free
- revoke is VIP-only
- both quick and custom extension forms are finite-active-VIP-only
- crafted extension POSTs cannot create VIP from Free
- Lifetime VIP cannot be extended
- invalid extension requests return HTTP 422
- no `users.is_vip` / `vip_active` state is introduced

No database migration is required.
