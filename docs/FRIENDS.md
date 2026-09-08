# Friends module

Friends 1.0.0 is an optional mutual-contact module. Install and enable it from `/admin/modules`; users manage received requests, sent requests, accepted friends and their block list at `/friends`.

## Relationship flow

- A registered user sends one pending request to another active account.
- Only the recipient may accept or decline that request.
- Either participant may remove an accepted friendship.
- Blocking removes any friendship or pending request and prevents new requests in either direction.
- Unblocking does not restore the previous relationship.

All mutations use POST, CSRF validation and participant-constrained SQL. The sorted user pair is the relationship primary key, preventing duplicate or crossed requests.

## Optional integrations

Friends listens to `profile.actions.building` and `profile.statistics.building`. An accepted friend gets a private-message composer link only when Private Messages is active. It emits `friend.requested` and `friend.accepted`; Notifications may consume them, but Friends never requires Notifications.
