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
        private readonly PrivateSiteAccessPolicy $privateSite = new PrivateSiteAccessPolicy(),
        private readonly PasswordChangeAccessPolicy $passwordChange = new PasswordChangeAccessPolicy(),
    ) {
        $this->errors->register();
    }

    public function handle(Request $request): Response
    {
        try {
            $this->container->get(Application::class)->boot();
            if ($this->maintenance->blocks($request)) {
                return $this->securityHeaders->apply(Response::html(
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
                return $this->securityHeaders->apply(Response::redirect('/login?private=1'));
            }
            if ($authenticatedUser !== null && $this->passwordChange->blocks($request->path(), (bool) ($authenticatedUser['must_change_password'] ?? false))) {
                return $this->securityHeaders->apply(Response::redirect('/account/profile?password_required=1'));
            }
            $user = null;
            $allowed = false;
            if ($this->adminAccess->protects($request)) {
                $user = $authenticatedUser;
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
