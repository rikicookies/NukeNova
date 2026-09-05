<?php

declare(strict_types=1);

namespace NovaNuke\Core\Http;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\Security\AdminAccessGate;
use NovaNuke\Core\Security\AuthorizationService;
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
        private readonly AdminAccessGate $adminAccess,
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
            $user = null;
            $allowed = false;
            if ($this->adminAccess->protects($request)) {
                $user = $this->container->get(AuthManager::class)->user();
                $allowed = $user !== null && $this->container->get(AuthorizationService::class)
                    ->allows((int) $user['id'], 'admin.access');
            }
            $adminGuard = $this->adminAccess->guard(
                $request,
                $user,
                $allowed,
            );
            if ($adminGuard !== null) {
                return $this->securityHeaders->apply($adminGuard);
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
