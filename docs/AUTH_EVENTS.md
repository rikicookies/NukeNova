# Authentication events

NovaNuke dispatches small synchronous authentication events after the associated database operation succeeds. Payloads deliberately omit email addresses, passwords, tokens, IP addresses, user-agent strings and request objects.

| Event | Payload | Dispatched after |
| --- | --- | --- |
| `user.registered` | `UserRegistered(userId, verificationRequired)` | Account, profile and Member role are committed. |
| `user.email_verified` | `UserEmailVerified(userId)` | A pending registration becomes active. |
| `user.logged_in` | `UserLoggedIn(userId)` | Session regeneration and login-history recording succeed. |
| `user.email_changed` | `UserEmailChanged(userId)` | A new account address is confirmed. |
| `user.anonymized` | `UserAnonymized(userId)` | Core personal data is irreversibly anonymized. |

Modules register listeners in `boot()`:

```php
$context->events->listen('user.registered', static function (object $event): void {
    if (! $event instanceof \NovaNuke\Auth\UserRegistered) return;
    // Queue or record module-owned work using $event->userId.
});
```

Listeners run in priority order. Authentication events are notifications, not veto points: they run after the core operation and listener exceptions are logged without reversing or blocking the successful account action. A listener should do minimal work and must never expect secrets in the payload.
