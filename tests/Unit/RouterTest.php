<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Http\Routing\MethodNotAllowed;
use NovaNuke\Core\Http\Routing\RouteNotFound;
use NovaNuke\Core\Http\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testItMatchesAParameterizedRoute(): void
    {
        $router = new Router();
        $router->get('/news/{slug}', static fn (): Response => Response::html('ok'));

        $match = $router->match(Request::create('GET', '/news/first-story'));

        self::assertSame('first-story', $match->parameters['slug']);
    }

    public function testItDistinguishesMissingRoutesFromInvalidMethods(): void
    {
        $router = new Router();
        $router->get('/news', static fn (): Response => Response::html('ok'));

        try {
            $router->match(Request::create('POST', '/news'));
            self::fail('Expected MethodNotAllowed.');
        } catch (MethodNotAllowed) {
            self::assertTrue(true);
        }

        $this->expectException(RouteNotFound::class);
        $router->match(Request::create('GET', '/missing'));
    }
}
