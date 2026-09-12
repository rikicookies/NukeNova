# NovaNuke 0.3.0-beta.2

## Production hardening — installer and storage boundaries

- Fresh installs now provision all required writable runtime directories automatically.
- `install:check` creates missing storage directories before checking permissions.
- Required private paths include downloads, backups, avatars and Wiki storage.
- `public/uploads` is also provisioned as part of the runtime layout.
- Symlinked required storage boundaries are rejected.
- Download storage defensively creates its private directory if it is missing at runtime.
- Fresh `.env` files include the Beta 1 session lifetime, idle-timeout and rotation defaults.

No database migration is required.
