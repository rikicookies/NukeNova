# UX-AJAX-03

UX-AJAX-03 extends NovaNuke's existing progressive enhancement to focused membership actions.

## Included

- Assign an existing membership plan.
- Extend an active non-lifetime VIP grant.
- Revoke active VIP access.
- Cancel an already scheduled VIP grant.

Each form keeps its existing `POST` route, CSRF token, authorization checks, activity logging, and redirect fallback. With JavaScript enabled, the server-rendered membership detail section is replaced in place without moving the viewport.

## Deliberately excluded

- Blocks remain traditional because their current endpoint saves the complete editor rather than exposing isolated toggle operations.
- User status and roles remain traditional because they share a security-sensitive account editor.
- Menu ordering remains handled by its dedicated scripts; replacing the sorter would require a wider lifecycle change.
- Membership scheduling remains traditional because it is a larger date/plan form rather than a frequent one-click action.

The shared helper now rejects duplicate submissions while a request is pending and reports a missing replacement fragment as an error instead of claiming success.

No routes, controllers, database migrations, membership rules, plans, or CMS version were changed.
