# NovaNuke 0.3.0-beta.3

## Production hardening — file boundaries and private delivery

This release standardizes storage containment across Downloads, Wiki attachments, avatars and Media.

- Private storage roots reject symbolic-link boundaries.
- Resolved stored files must remain inside their real storage root.
- Symlinked stored files are rejected.
- Downloads and Wiki attachments keep authorization checks ahead of private path resolution.
- Wiki inline attachments retain `private, no-store`.
- Avatars remain public by design, with immutable caching and `nosniff`.
- Media deletion uses the same shared containment primitive.

No database migration is required.
