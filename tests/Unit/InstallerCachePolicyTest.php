<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Routing\Router;
use PHPUnit\Framework\TestCase;

final class InstallerCachePolicyTest extends TestCase
{
    public function testPreInstallHomeRedirectCannotBeCachedByTheBrowser(): void
    {
        $router = new Router();
        require dirname(__DIR__, 2) . '/routes/installer.php';

        $match = $router->match(Request::create('GET', '/'));
        $response = ($match->route->handler)();

        self::assertSame(302, $response->status());
        self::assertSame('/install', $response->header('Location'));
        self::assertSame('no-store, no-cache, must-revalidate, max-age=0', $response->header('Cache-Control'));
        self::assertSame('no-cache', $response->header('Pragma'));
        self::assertSame('0', $response->header('Expires'));
    }
}
