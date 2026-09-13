<?php

declare(strict_types=1);

namespace NovaNuke\Core\Http;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Application;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\Security\AdminAccessGate;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\SecurityHeaders;
use NovaNuke\Core\System\MaintenanceMode;
use NovaNuke\Core\System\PrivateSiteAccessPolicy;
use NovaNuke\Core\System\PasswordChangeAccessPolicy;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Core\Modules\ModuleRouteAccess;
use Throwable;

final class Kernel
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly ErrorHandler $errors,
        private readonly SecurityHeaders $securityHeaders,
        private readonly ?MaintenanceMode $maintenance,
        private readonly ?AdminAccessGate $adminAccess,
        private readonly PrivateSiteAccessPolicy $privateSite = new PrivateSiteAccessPolicy(),
        private readonly PasswordChangeAccessPolicy $passwordChange = new PasswordChangeAccessPolicy(),
        private readonly ?ModuleRouteAccess $moduleAccess = null,
        private readonly bool $installed = true,
    ) {
        $this->errors->register();
    }

    public function handle(Request $request): Response
    {
        try {
            $this->container->get(Application::class)->boot();

            // Installer mode must remain database-independent. Before installation
            // there is no trusted DB configuration yet, so do not resolve auth,
            // settings, maintenance, authorization or module services.
            if (! $this->installed) {
                return $this->dispatch($request);
            }

            if ($this->maintenance?->blocks($request)) {
                return $this->secure($request, Response::html(
                    '<!doctype html><html lang="en"><meta charset="utf-8"><title>Maintenance</title>'
                    . '<main><h1>We will be back shortly.</h1><p>The site is undergoing scheduled maintenance.</p></main>',
                    503,
                )->withHeader('Retry-After', '900')->withHeader('Cache-Control', 'no-store'));
            }
            $auth = $this->container->get(AuthManager::class);
            $authenticatedUser = $auth->user();
            if ($this->privateSite->blocks(
                $request->path(),
                $this->container->get(SettingsRepository::class)->boolean('users.private_site', false),
                $authenticatedUser !== null,
            )) {
                return $this->secure($request, Response::redirect('/login?private=1'));
            }
            if ($authenticatedUser !== null && $this->passwordChange->blocks($request->path(), (bool) ($authenticatedUser['must_change_password'] ?? false))) {
                return $this->secure($request, Response::redirect('/account/profile?password_required=1'));
            }
            $user = null;
            $allowed = false;
            if ($this->adminAccess?->protects($request)) {
                $user = $authenticatedUser;
                $allowed = $user !== null && $this->container->get(AuthorizationService::class)
                    ->allows((int) $user['id'], 'admin.access');
            }
            $adminGuard = $this->adminAccess?->guard(
                $request,
                $user,
                $allowed,
            );
            if ($adminGuard !== null) {
                return $this->secure($request, $adminGuard);
            }
            return $this->dispatch($request);
        } catch (Throwable $error) {
            return $this->secure($request, $this->errors->render($error));
        }
    }
    private function dispatch(Request $request): Response
    {
        $match = $this->router->match($request);
        if ($this->installed && $this->moduleAccess !== null
            && ($moduleGuard = $this->moduleAccess->guard($match->route, $request)) !== null) {
            return $this->secure($request, $moduleGuard);
        }

        $request = $request->withAttributes($match->parameters);
        $response = ($match->route->handler)($request, $this->container);

        if (! $response instanceof Response) {
            throw new \LogicException('Route handlers must return a Response.');
        }

        return $this->secure($request, $response);
    }

    private function secure(Request $request, Response $response): Response
    {
        $path = $request->path();
        $sensitive = $response->status() >= 400
            || str_starts_with($path, '/admin')
            || str_starts_with($path, '/account')
            || str_starts_with($path, '/login')
            || str_starts_with($path, '/register')
            || str_starts_with($path, '/password');

        if ($sensitive && $response->header('Cache-Control') === null) {
            $response = $response->withHeader('Cache-Control', 'no-store, private');
        }

        return $this->securityHeaders->apply($response);
    }

}
