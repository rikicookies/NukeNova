<?php

declare(strict_types=1);

namespace NovaNuke\Auth;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;

final class AuthController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly LoginThrottle $throttle,
        private readonly LoginValidator $validator,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
    ) {
    }

    public function showLogin(): Response
    {
        if ($this->auth->user() !== null) {
            return Response::redirect('/admin');
        }

        return Response::html($this->views->render('auth/login.twig', [
            'csrf_token' => $this->csrf->token(),
            'errors' => [],
            'old_login' => '',
        ]));
    }

    public function login(Request $request): Response
    {
        $input = $request->allInput();
        if (! $this->csrf->validate($input['_token'] ?? null)) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $errors = $this->validator->validate($input);
        $login = trim((string) ($input['login'] ?? ''));
        $key = hash('sha256', strtolower($login) . '|' . $request->ip());

        if ($this->throttle->tooManyAttempts($key)) {
            $errors['login'] = 'Too many login attempts. Try again in ' . $this->throttle->retryAfter($key) . ' seconds.';
        }

        if ($errors === []) {
            $user = $this->auth->attempt($login, (string) $input['password'], $request->ip(), $request->userAgent());
            if ($user !== null) {
                $this->throttle->clear($key);
                $this->csrf->rotate();
                return Response::redirect('/admin');
            }

            $this->throttle->hit($key);
            $errors['login'] = 'The credentials are incorrect or the account is unavailable.';
        }

        return Response::html($this->views->render('auth/login.twig', [
            'csrf_token' => $this->csrf->token(),
            'errors' => $errors,
            'old_login' => $login,
        ]), 422);
    }

    public function logout(Request $request): Response
    {
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $this->auth->logout();
        return Response::redirect('/login');
    }
}
