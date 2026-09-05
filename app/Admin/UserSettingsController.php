<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Logging\ActivityLogger;

final class UserSettingsController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly SettingsRepository $settings,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
    ) {
    }

    public function show(): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }

        return $this->view(false);
    }

    public function update(Request $request): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $this->settings->setBoolean('users.registration_open', $request->input('registration_open') === '1', 'users');
        $this->settings->setBoolean(
            'users.email_verification_required',
            $request->input('email_verification_required') === '1',
            'users',
        );
        $user = $this->auth->user();
        $this->activity->log(
            (int) $user['id'],
            'settings.users.updated',
            'settings',
            'users',
            [
                'registration_open' => $request->input('registration_open') === '1',
                'verification_required' => $request->input('email_verification_required') === '1',
            ],
            $request->ip(),
        );
        $this->csrf->rotate();

        return $this->view(true);
    }

    private function guard(): ?Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }
        if (! $this->authorization->allows((int) $user['id'], 'settings.manage')) {
            return Response::html('Forbidden', 403);
        }

        return null;
    }

    private function view(bool $saved): Response
    {
        return Response::html($this->views->render('admin/user-settings.twig', [
            'csrf_token' => $this->csrf->token(),
            'registration_open' => $this->settings->boolean('users.registration_open', false),
            'verification_required' => $this->settings->boolean('users.email_verification_required', true),
            'saved' => $saved,
        ]));
    }
}
