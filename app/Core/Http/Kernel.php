<?php

declare(strict_types=1);

namespace NovaNuke\Core\Http;

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\Security\SecurityHeaders;
use NovaNuke\Core\System\MaintenanceMode;
use Throwable;

final class Kernel
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly ErrorHandler $errors,
        private readonly SecurityHeaders $securityHeaders,
        private readonly MaintenanceMode $maintenance,
    ) {
        $this->errors->register();
    }

    public function handle(Request $request): Response
    {
        try {
            if ($this->maintenance->blocks($request)) {
                return $this->securityHeaders->apply(Response::html(
                    '<!doctype html><html lang="en"><meta charset="utf-8"><title>Maintenance</title>'
                    . '<main><h1>We will be back shortly.</h1><p>The site is undergoing scheduled maintenance.</p></main>',
                    503,
                )->withHeader('Retry-After', '900')->withHeader('Cache-Control', 'no-store'));
            }
            $match = $this->router->match($request);
            $request = $request->withAttributes($match->parameters);
            $response = ($match->route->handler)($request, $this->container);

            if (! $response instanceof Response) {
                throw new \LogicException('Route handlers must return a Response.');
            }

            return $this->securityHeaders->apply($response);
        } catch (Throwable $error) {
            return $this->securityHeaders->apply($this->errors->render($error));
        }
    }
}
