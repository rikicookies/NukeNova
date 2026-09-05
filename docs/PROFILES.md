# Member profiles and preferences

NovaNuke 0.1.0-alpha.5 gives each registered user an account editor at `/account/profile` and a friendly public URL at `/users/{username}`.

## Preferences

Users may edit their display name, plain-text biography, English or Spanish interface locale, PHP timezone and profile visibility. Personal locale and timezone override the site defaults only while that user is signed in. Profile visibility supports:

- `public`: available to visitors and members;
- `members`: returns 403 to guests and remains available to authenticated members.

Email addresses, roles, login history and administrative status are never rendered on public profiles.

## Avatars

Avatar files live in `storage/private/avatars/`, outside the web document root. `/avatars/{generated-name}` validates a server-generated filename and serves the file with its known image MIME, `nosniff` and immutable cache metadata.

Uploads require all of the following:

- JPEG, PNG or WebP content verified through Fileinfo and image metadata;
- maximum 2 MB size;
- dimensions between 32×32 and 2048×2048 pixels;
- a cryptographically random server filename;
- an authenticated account and valid CSRF token.

The browser-provided filename and extension are not trusted. SVG, GIF and executable files are not accepted. Preserve `storage/private/avatars/` in backups and updates.

## Password changes

The account editor requires the current password and the existing 12–255 character password policy. Failed attempts are limited per account. A successful change increments `auth_version`, removes outstanding recovery tokens, writes a value-free activity event, invalidates authenticated sessions and requires a fresh login.
