# Audiences and VIP access

NovaNuke separates permanent authorization from temporary access. Roles and permissions decide what a user may administer; audiences decide who may view a module, block or content item. VIP is an expiring entitlement attached to the existing user account, not a separate account type or role.

## Audience values

- `public`: visible to everyone;
- `guest`: visible only while signed out; available for modules and blocks;
- `member`: visible to every authenticated active account;
- `vip`: visible only to authenticated accounts with an active VIP entitlement.

Pages and Downloads retain their earlier internal value `members` for compatibility. It has the same meaning as the shared `member` audience. Their existing selected-role option remains independent of VIP.

## Managing VIP

Only a Super Administrator may grant, extend or revoke VIP from user administration. A grant has a UTC start and expiration time. Granting more days while VIP is active extends the latest expiration; granting after expiration creates a new active period. Revocation takes effect immediately.

Expiration does not suspend, downgrade or modify the account. The user retains normal member access, roles, profile and content; only VIP-marked areas become unavailable. NovaNuke alpha.20 has no payments, plans, recurring billing or automatic renewal.

## Enforcement

Visibility in a menu or template is never the security boundary. NovaNuke enforces:

- module audiences at the HTTP route boundary;
- block audiences before role, schedule and page filters;
- content audiences in repositories/controllers for lists and details;
- VIP checks again before download delivery, link redirection, reports and comments;
- public-only filtering for RSS and sitemap output.

Guests attempting a restricted content detail are normally redirected to login. Authenticated users without the required entitlement receive HTTP 403 or a non-disclosing not-found response on sensitive action routes.

## Extension guidance

Future modules should reuse `AccessAudience` for route-level audiences and `EntitlementService::VIP` when a repository must authorize individual records. Store a controlled audience value, validate it server-side, default existing data to `public`, filter paginated queries before counting rows, and repeat authorization on every direct action route.

Do not use VIP as an administrative permission and do not infer VIP from a hidden button, role name, cookie or request value.
