# Event API

NovaNuke Module API 1.0 uses a small synchronous event dispatcher. The public dispatcher remains string based for compatibility, but Core and bundled modules reference stable names through `NovaNuke\Core\Events\EventName` instead of repeating magic strings.

```php
use NovaNuke\Core\Events\EventName;

$context->events->listen(EventName::CONTENT_CREATED, static function (object $event): void {
    if (! $event instanceof \NovaNuke\Core\Content\ContentChanged) return;
});
```

Listeners run synchronously in descending priority. A listener should do minimal work, must not assume another optional module is enabled, and should only mutate payloads explicitly designed as collecting/building/checking extension points.

## Core-owned extension payloads

The following cross-module hooks have Core-owned payload contracts:

- `admin.menu.building` → `NovaNuke\Core\Admin\AdminMenuBuilding`
- `profile.actions.building` → `NovaNuke\Core\Profile\ProfileActionsBuilding`
- `profile.statistics.building` → `NovaNuke\Core\Profile\ProfileStatisticsBuilding`
- `content.created` / `content.updated` → `NovaNuke\Core\Content\ContentChanged`
- `comments.content.checking` → `NovaNuke\Core\Comments\CommentTargetChecking`
- `comment.created` → `NovaNuke\Core\Comments\CommentCreated`
- `media.usage.checking` → `NovaNuke\Core\Media\MediaUsageChecking`
- `friend.requested` / `friend.accepted` → Core Social payloads
- `private-message.sent` → Core Messaging payload
- `maintenance.pruning` → `NovaNuke\Core\Maintenance\MaintenancePruning`
- `search.providers.registering` → `NovaNuke\Core\Search\SearchProvidersRegistering`
- `sitemap.collecting` → `NovaNuke\Core\Sitemap\SitemapCollecting`

Module-specific rendering/download hooks may continue using module-owned payloads when consumers are intentionally integrating with that module.

## Generic content lifecycle

News, Pages and Wiki continue dispatching their historical payload classes for compatibility, but those classes now extend `NovaNuke\Core\Content\ContentChanged`. New cross-module listeners should type against the Core class.

`ContentChanged` exposes:

- `type` / `contentType` — stable content family identifier;
- `id` — module-owned numeric record ID;
- `actorId` — user that performed the change;
- `reference` — optional stable module-owned reference, such as a Wiki path.

This keeps old `instanceof Modules\...` listeners working while allowing new listeners to consume one Core contract.

## Compatibility

`NovaNuke\Auth\ProfileActionsBuilding` and `NovaNuke\Auth\ProfileStatisticsBuilding` remain available as compatibility subclasses of the Core profile contracts. Existing bundled News/Pages/Wiki content payload names also remain available.

The Event API does not make delivery asynchronous and does not turn notification events into transaction veto points. Authentication event error isolation remains documented separately in `AUTH_EVENTS.md`.

## Membership lifecycle

- `membership.assigned` → `NovaNuke\Core\Membership\MembershipAssigned`
- `membership.revoked` → `NovaNuke\Core\Membership\MembershipRevoked`
- `membership.expired` → `NovaNuke\Core\Membership\MembershipExpired`

`membership.expired` is emitted by maintenance processing once per expired entitlement grant. Expiration remains date-driven; NovaNuke does not store a `users.is_vip` flag.

- `membership.scheduled` → `NovaNuke\Core\Membership\MembershipScheduled`
- `membership.schedule_cancelled` → `NovaNuke\Core\Membership\MembershipScheduleCancelled`

- `membership.activated` → `NovaNuke\Core\Membership\MembershipActivated`

- `membership.extended` → `NovaNuke\Core\Membership\MembershipExtended`
