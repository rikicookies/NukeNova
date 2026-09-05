# Notifications module

Notifications provides a private in-site inbox at `/notifications`. Install and enable it from `/admin/modules`. Signed-in users see an unread counter in the bundled default theme and can mark one notification or the entire inbox as read.

## Initial integrations

- `private-message.sent` creates a notification for the recipient without copying the private message body.
- `comment.created` notifies active users with `comments.moderate` only when the comment is pending.
- `maintenance.pruning` removes notifications that have been read for more than 90 days.

Update the Private Messages module to version 1.1.0 after installing this release so it emits the message event. Notifications remains optional: sending a private message still works when the module is disabled.

## Publishing from another module

Enabled modules may obtain `Modules\Notifications\src\NotificationPublisher` from the container when they explicitly depend on Notifications. Prefer a small typed event when the integration should remain optional.

```php
$publisher->toUser(
    $userId,
    'orders.ready',
    'Order ready',
    'Your order is ready for pickup.',
    '/orders/42',
    'order-ready:42',
);
```

Types use lowercase letters, numbers, dots and hyphens. Titles and messages are length-limited. URLs must be internal paths beginning with one slash; external URLs and protocol-relative links are rejected. A per-user deduplication key prevents repeated delivery when an event is handled more than once.

Never put passwords, reset tokens, private message bodies or other secrets in a notification. Authorization must still be enforced by the destination controller—the presence of a link grants no access.
