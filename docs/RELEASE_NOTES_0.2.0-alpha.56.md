# NovaNuke 0.2.0-alpha.56

Alpha.56 continues Phase 4 — Module Contracts / Internal API. It removes bundled cross-module implementation imports for optional Comments, Media and Private Messages integrations and moves shared event payload ownership into Core.

## Highlights

- Core `CommentProviderInterface`, `MediaLibraryInterface` and `PrivateMessageComposerInterface`.
- Core-owned integration payloads for Comments, Media, Friends and Private Messages.
- Module implementations bind their Core contracts only when enabled, preserving optional discovery during boot.
- Compatibility aliases preserve former module-owned payload class names.
- Notifications consumes Core events rather than importing Friends/Comments/PrivateMessages internals.
- Demo Content remains the explicit concrete-orchestration exception.
- No schema, migration, permission, route or Composer dependency changes.

## Upgrade

Direct upgrade source: `0.2.0-alpha.55`. Run the normal backup, test, migration-status, release-check and `upgrade:complete` workflow before recording the new version.
