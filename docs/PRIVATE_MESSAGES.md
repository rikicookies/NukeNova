# Private Messages module

Private Messages 1.0.0 provides authenticated, asynchronous conversations between registered users. It is deliberately not a live chat system.

## Features

- inbox with unread state;
- sent-message history;
- two-user conversations and replies;
- participant-specific conversation removal;
- user blocking in both sending directions;
- abuse reports and an administrative moderation queue;
- 20 sends per hour and 5 reports per hour per account;
- plain-text bodies with Twig output escaping and CSRF-protected mutations.

Install and enable the module at `/admin/modules`. Users access it at `/messages`; moderators with `private-messages.moderate` use `/admin/private-messages`. Add `/messages` to an account menu if desired.

## Data and deletion

A conversation stores one canonical copy of each message. Removing it from an inbox sets only that participant's `deleted_at` state, so the other participant retains their history. A later reply restores the conversation to both inboxes. Uninstalling while preserving data keeps all conversations; choosing data deletion drops every module table.

Blocking prevents either participant from sending more messages while the block exists. It does not erase existing history. Reports expose the reported message, sender, reporter and reason only to authorized moderators.

## Security limitations

Message bodies are not end-to-end encrypted. Database and hosting administrators can access stored messages, so the interface must not claim otherwise. Rate limits reduce automated abuse but cannot eliminate it. A future notification hook can be added without turning this feature into real-time chat.
