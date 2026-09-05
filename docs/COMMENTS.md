# Comments module

Comments is an optional reusable module. It owns its tables, routes, moderation permission and settings; content modules decide which individual records accept comments.

## Install and configure

Copy the trusted `modules/Comments` directory to the server, then install and enable it from `/admin/modules`. A user with `comments.moderate` can open `/admin/comments` to allow guest submissions and choose whether new comments require approval. Both settings are conservative by default: guests are disabled and moderation is required.

Disabling the module preserves comments and reports. During controlled uninstallation, choosing to delete data runs the module's down migration.

## Content-provider contract

Before a comment is created, the module dispatches `comments.content.checking` with a `CommentTargetChecking` object. A content module must verify that the record exists, is publicly visible and has comments enabled, then call `accept()`.

```php
$events->listen('comments.content.checking', function (object $event) use ($repository): void {
    if ($event instanceof CommentTargetChecking
        && $event->type === 'example'
        && $repository->publicRecordAcceptsComments($event->contentId)) {
        $event->accept();
    }
});
```

Never accept only from the submitted type and ID. The provider must query its own published record and enforce its own access rules. News 1.1.0 demonstrates this contract.

## Rendering

Content controllers may resolve `CommentService` only when it is registered. Call `for($type, $id)` for the approved thread, then include `@comments/thread.twig`. This keeps the content module operational when Comments is disabled.

## Security behavior

- Bodies are plain text, stripped of tags and escaped by Twig.
- CSRF protects create, edit, report and moderation actions.
- Registered authors can edit their own comments for 15 minutes.
- Replies are limited to five levels and must target an approved comment on the same content.
- Comment and report submissions use the persistent database rate limiter.
- Guest identity and comment IP values are stored only as keyed hashes in module tables.
- One identity can report a particular comment once.
- Server-side permission checks protect every administrative action.

Rate limits and identity hashes reduce obvious abuse; they do not prove that two guests are different people. Production sites should also apply web-server request limits and review moderation queues.
