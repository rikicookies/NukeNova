<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\Themes\ThemeManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class ThemesController
{
    public function __construct(
        private readonly ThemeManager $themes,
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
        private readonly SessionManager $session,
        private readonly ViewRenderer $views,
    ) {
    }

    public function index(): Response
    {
        $guard = $this->guard();

        return $guard ?? $this->view(
            is_string($message = $this->session->pull('themes.message')) ? $message : null,
        );
    }

    public function action(Request $request): Response
    {
        $guard = $this->guard();
        if ($guard !== null) {
            return $guard;
        }
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }
        $slug = (string) $request->attribute('slug');
        $action = (string) $request->attribute('action');
        if (! in_array($action, ['install', 'update', 'activate', 'configure', 'uninstall'], true)) {
            return Response::html('Unsupported theme action.', 404);
        }

        try {
            switch ($action) {
                case 'install': $this->themes->install($slug); break;
                case 'update': $this->themes->update($slug); break;
                case 'activate': $this->themes->activate($slug); break;
                case 'configure': $this->themes->configure($slug, $request->allInput()); break;
                case 'uninstall':
                    if (! hash_equals($slug, (string) $request->input('confirm_slug'))) {
                        throw new RuntimeException("Type the theme slug ({$slug}) to confirm uninstallation.");
                    }
                    $this->themes->uninstall($slug);
                    break;
            }
            $actor = $this->auth->user();
            $this->activity->log((int) $actor['id'], "theme.{$action}", 'theme', $slug, [], $request->ip());
            $this->session->put('themes.message', "Theme action completed: {$action}.");

            return Response::redirect('/admin/themes', 303);
        } catch (RuntimeException $error) {
            return $this->view(null, $error->getMessage(), 422);
        }
    }

    private function guard(): ?Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        return $this->authorization->allows((int) $user['id'], 'themes.manage')
            ? null
            : Response::html('Forbidden', 403);
    }

    private function view(?string $message = null, ?string $error = null, int $status = 200): Response
    {
        return Response::html($this->views->render('admin/themes/index.twig', [
            'themes' => $this->themes->inventory(),
            'csrf_token' => $this->csrf->token(),
            'message' => $message,
            'error' => $error,
        ]), $status);
    }
}
