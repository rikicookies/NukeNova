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

    public function testItRejectsDuplicateRouteNames(): void
    {
        $router = new Router();
        $router->get('/first', static fn (): Response => Response::html('first'), 'example.route');

        $this->expectException(\InvalidArgumentException::class);
        $router->get('/second', static fn (): Response => Response::html('second'), 'example.route');
    }

    public function testItRejectsExactMethodAndPathCollisionsAcrossOwners(): void
    {
        $router = new Router();
        $router->get('/shared', static fn (): Response => Response::html('core'));
        $router->beginOwner('example');

        try {
            $router->get('/shared', static fn (): Response => Response::html('module'));
            self::fail('Expected route collision to be rejected.');
        } catch (\InvalidArgumentException $error) {
            self::assertStringContainsString('module example', $error->getMessage());
        } finally {
            $router->endOwner();
        }
    }

    public function testSamePathMayUseDifferentHttpMethods(): void
    {
        $router = new Router();
        $router->get('/settings', static fn (): Response => Response::html('form'));
        $router->post('/settings', static fn (): Response => Response::html('saved'));

        self::assertSame(['GET', 'HEAD'], $router->match(Request::create('GET', '/settings'))->route->methods);
        self::assertSame(['POST'], $router->match(Request::create('POST', '/settings'))->route->methods);
    }

}
