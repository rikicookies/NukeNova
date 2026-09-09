<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiNavigation;
use PHPUnit\Framework\TestCase;

final class WikiNavigationTest extends TestCase
{
    public function testItOnlyListsDirectPagesAndImmediateChildNamespaces(): void
    {
        $pages = [
            ['namespace' => '', 'slug' => 'home', 'title' => 'Home'],
            ['namespace' => 'guides', 'slug' => 'start', 'title' => 'Start'],
            ['namespace' => 'guides:admin', 'slug' => 'users', 'title' => 'Users'],
            ['namespace' => 'api', 'slug' => 'auth', 'title' => 'Auth'],
        ];

        $root = (new WikiNavigation())->directory($pages, '');
        self::assertSame(['home'], array_column($root['pages'], 'slug'));
        self::assertSame(['api', 'guides'], array_column($root['namespaces'], 'path'));

        $guides = (new WikiNavigation())->directory($pages, 'guides');
        self::assertSame(['start'], array_column($guides['pages'], 'slug'));
        self::assertSame(['guides:admin'], array_column($guides['namespaces'], 'path'));
    }

    public function testItBuildsLinkedNamespaceBreadcrumbsForAPage(): void
    {
        $breadcrumbs = (new WikiNavigation())->pageBreadcrumbs('guides:admin:user-roles', 'User roles');

        self::assertSame(['Wiki', 'Guides', 'Admin', 'User roles'], array_column($breadcrumbs, 'label'));
        self::assertSame('/wiki?namespace=guides%3Aadmin', $breadcrumbs[2]['url']);
        self::assertNull($breadcrumbs[3]['url']);
    }

    public function testItBuildsACompleteNestedWikiMap(): void
    {
        $map = (new WikiNavigation())->sitemap([
            ['namespace' => 'guides:admin', 'slug' => 'users', 'title' => 'Users'],
            ['namespace' => '', 'slug' => 'home', 'title' => 'Home'],
            ['namespace' => 'guides', 'slug' => 'start', 'title' => 'Start'],
        ]);

        self::assertSame('home', $map['pages'][0]['path']);
        self::assertSame('guides', $map['namespaces'][0]['path']);
        self::assertSame('guides:start', $map['namespaces'][0]['pages'][0]['path']);
        self::assertSame('guides:admin', $map['namespaces'][0]['namespaces'][0]['path']);
        self::assertSame('guides:admin:users', $map['namespaces'][0]['namespaces'][0]['pages'][0]['path']);
    }
}
