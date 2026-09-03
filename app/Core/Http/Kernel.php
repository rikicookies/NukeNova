<?php

declare(strict_types=1);

namespace NovaNuke\Core\Http;

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Routing\Router;
use Throwable;

final class Kernel
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly ErrorHandler $errors,
    ) {
        $this->errors->register();
    }

    public function handle(Request $request): Response
    {
        try {
            $match = $this->router->match($request);
            $request = $request->withAttributes($match->parameters);
            $response = ($match->route->handler)($request, $this->container);

            if (! $response instanceof Response) {
                throw new \LogicException('Route handlers must return a Response.');
            }

            return $response;
        } catch (Throwable $error) {
            return $this->errors->render($error);
        }
    }
}
