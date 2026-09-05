# Account security and lifecycle

Signed-in members can open `/account/security` to review their 20 most recent successful sign-ins. NovaNuke shows the UTC timestamp, IP address and a conservative browser/platform label. User-agent strings are never rendered directly.

## Controlled account deletion

Account deletion requires a valid CSRF token, the exact current username and the current password. Failed attempts are rate limited. The last active Super Administrator cannot delete their account; another active Super Administrator must be assigned first.

NovaNuke uses irreversible logical deletion instead of deleting the user row. It:

- replaces the username and email with random non-contactable identifiers;
- replaces the password and invalidates every authenticated session;
- removes reset, verification and pending email-change tokens, roles and login history;
- clears profile details, preferences, avatar reference and prior IP addresses in activity logs;
- removes the private avatar file after the database transaction;
- dispatches `user.anonymized` with only the numeric user ID.

Authored news, pages, comments and other module records remain intact for referential integrity and publication history. They resolve to a non-personal `former-user-ID` identity. Private-message content already delivered to another participant is retained; the former sender identity is anonymized.

Modules that store additional personal data can listen for `user.anonymized` and remove it without modifying the core. Listeners run after the core transaction; NovaNuke logs a listener failure without undoing the completed anonymization.
