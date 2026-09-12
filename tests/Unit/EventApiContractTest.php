<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\News\src\ContentChanged as NewsContentChanged;
use Modules\Pages\src\PageChanged;
use Modules\Wiki\src\WikiPageChanged;
use NovaNuke\Core\Content\ContentChanged;
use NovaNuke\Core\Events\EventName;
use NovaNuke\Core\Profile\ProfileActionsBuilding;
use NovaNuke\Core\Profile\ProfileStatisticsBuilding;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final class EventApiContractTest extends TestCase
{
    public function testCoreEventNamesAreStableAndUnique(): void
    {
        $events = array_values((new \ReflectionClass(EventName::class))->getConstants());

        self::assertSame(count($events), count(array_unique($events)));
        foreach ([
            'admin.menu.building',
            'content.created',
            'content.updated',
            'profile.actions.building',
            'profile.statistics.building',
            'maintenance.pruning',
            'search.providers.registering',
            'sitemap.collecting',
        ] as $event) {
            self::assertContains($event, $events);
        }
    }

    public function testSharedProfilePayloadsAreCoreOwnedWithLegacyAliases(): void
    {
        self::assertTrue(class_exists(\NovaNuke\Auth\ProfileActionsBuilding::class));
        self::assertTrue(class_exists(\NovaNuke\Auth\ProfileStatisticsBuilding::class));

        $actions = new \NovaNuke\Auth\ProfileActionsBuilding(10, 'alice', 20);
        $statistics = new \NovaNuke\Auth\ProfileStatisticsBuilding(10);

        self::assertInstanceOf(ProfileActionsBuilding::class, $actions);
        self::assertInstanceOf(ProfileStatisticsBuilding::class, $statistics);
    }

    public function testGenericContentPayloadUnifiesBundledContentEvents(): void
    {
        $news = new NewsContentChanged('news', 1, 7);
        $page = new PageChanged('pages', 2, 7);
        $wiki = new WikiPageChanged(3, 'reference:events', 7);

        foreach ([$news, $page, $wiki] as $event) {
            self::assertInstanceOf(ContentChanged::class, $event);
            self::assertSame(7, $event->actorId);
        }

        self::assertSame('news', $news->type);
        self::assertSame('pages', $page->contentType);
        self::assertSame('wiki', $wiki->type);
        self::assertSame('reference:events', $wiki->reference);
        self::assertSame('reference:events', $wiki->path);
    }

    public function testCoreAndBundledPhpUseEventNameConstantsForDispatchAndListen(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['app', 'modules'] as $directory) {
            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory)),
                '/\.php$/i',
            );
            foreach ($iterator as $file) {
                $path = $file->getPathname();
                if (str_ends_with(str_replace('\\', '/', $path), '/app/Core/Events/EventName.php')) {
                    continue;
                }
                $source = (string) file_get_contents($path);
                self::assertDoesNotMatchRegularExpression(
                    "/(?:dispatch|listen)\\(\\s*['\"][a-z][a-z0-9.-]+['\"]/",
                    $source,
                    'Raw event name in ' . $path,
                );
            }
        }
    }
}
