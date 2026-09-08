# Member profiles and preferences

Each registered user has an account editor at `/account/profile`, a friendly public URL at `/users/{username}` and an entry in the paginated `/users` directory when visibility permits.

## Preferences

Users may edit their display name, Markdown or sanitized-HTML biography, optional HTTP/HTTPS website, optional location, interface locale, PHP timezone and profile visibility. Personal locale and timezone override the site defaults only while that user is signed in. Profile visibility supports:

- `public`: available to visitors and members;
- `members`: returns 403 to guests and remains available to authenticated members.

Email addresses, roles, login history and administrative status are never rendered on public profiles.

Enabled modules may contribute small public statistics through `profile.statistics.building`. The bundled News, Comments and Friends modules expose only published, approved or accepted totals. Friends also uses `profile.actions.building` to add contextual relationship controls without coupling the profile controller to the module.

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
