# NovaNuke 0.2.0-alpha.17

This cumulative development release upgrades installations currently on 0.2.0-alpha.15. It contains alpha.16 private-message formats plus the new member-directory and social foundation.

## Highlights

- paginated `/users` directory and richer public profiles;
- optional Friends module with blocking and private-message shortcut;
- Like/Dislike reactions for comments;
- friendship notifications;
- modular public profile statistics;
- Markdown or sanitized HTML private messages.

## Update

Back up first and preserve `.env`, `composer.lock`, `storage/installed.lock`, `storage/private/` and all uploads. Replace files, then run:

```bash
composer install
php bin/cms migrate
php bin/cms cache:clear
```

Update Private Messages 1.2.0, Comments 1.2.0, Notifications 1.1.0 and News 1.7.0 from Admin → Modules. Install and enable Friends 1.0.0.

## Acceptance checks

- Open `/users` as a guest and signed-in member; members-only profiles must stay hidden from guests.
- Edit website/location and confirm unsafe website protocols are rejected.
- Send, accept, decline and remove a friend request; verify blocking removes the relationship.
- Confirm accepted friends get the message-composer shortcut only while Private Messages is active.
- Like, switch, and remove a comment reaction; verify guests only see totals.
- Confirm friend request/acceptance notifications when Notifications is active.
- Send Markdown and sanitized-HTML private messages and confirm unsafe markup is removed.
- Run `composer test`, `composer test:integration`, `php bin/cms release:check` and `php bin/cms migrate:status`.

No theme update is required. Blocks remain postponed technical debt and are unchanged by this release.
