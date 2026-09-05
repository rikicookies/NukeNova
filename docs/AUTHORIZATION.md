# NovaNuke authorization

NovaNuke authorizes actions with permission slugs. Hiding a menu item is never considered authorization; every controller or service that changes protected data must call `AuthorizationService::allows()` or a future policy that uses it.

## Core rules

- Users may have multiple roles.
- Roles may have multiple permissions.
- Permissions use stable dotted slugs such as `users.manage`.
- The `super-administrator` role has a protected server-side bypass.
- The Super Administrator role cannot be edited through the panel.
- A non-Super Administrator cannot assign or modify the Super Administrator role.
- NovaNuke prevents suspension or demotion of the final active Super Administrator.
- Account status and role changes are written to `activity_logs`.

## Checking a permission

```php
if (! $authorization->allows($userId, 'news.publish')) {
    return Response::html('Forbidden', 403);
}
```

Authorization checks belong both at the HTTP boundary and in sensitive application services when those services may be called from more than one entry point.

## Registering module permissions

Future modules must declare their permission slugs in `module.json` and register them during installation. Use the module slug as the prefix:

```text
news.edit
news.publish
downloads.manage
```

Never rename a released permission slug without a migration because role assignments reference its database identity.

## Default roles

- Super Administrator: protected bypass for every permission.
- Administrator: core administration, users, settings and logs, but cannot edit permission assignments or Super Administrators.
- Editor, Moderator, Member and Guest: no administrative access until modules assign appropriate permissions.
