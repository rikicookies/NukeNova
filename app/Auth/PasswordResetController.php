<?php

declare(strict_types=1);

namespace NovaNuke\Auth;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Core\Security\RateLimiter;
use RuntimeException;
use Throwable;

final class PasswordResetController
{
    public function __construct(
        private readonly PasswordResetService $resets,
        private readonly RateLimiter $throttle,
        private readonly PasswordPolicy $passwords,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
    ) {
    }

    public function showForgot(): Response
    {
        return Response::html($this->views->render('auth/forgot-password.twig', [
            'csrf_token' => $this->csrf->token(),
            'error' => null,
            'sent' => false,
        ]));
    }

    public function send(Request $request): Response
    {
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $email = strtolower(trim((string) $request->input('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::html($this->views->render('auth/forgot-password.twig', [
                'csrf_token' => $this->csrf->token(),
                'error' => 'Enter a valid email address.',
                'sent' => false,
            ]), 422);
        }

        $key = hash('sha256', 'reset|' . $email . '|' . $request->ip());
        if (! $this->throttle->tooManyAttempts($key)) {
            try {
                $this->resets->request($email, $request->ip());
            } catch (Throwable $error) {
                error_log(sprintf(
                    "[%s] Password reset delivery failed: %s%s",
                    gmdate('c'),
                    str_replace(["\r", "\n"], ' ', $error->getMessage()),
                    PHP_EOL,
                ));
            }
            $this->throttle->hit($key);
        }

        return Response::html($this->views->render('auth/forgot-password.twig', [
            'csrf_token' => $this->csrf->token(),
            'error' => null,
            'sent' => true,
        ]));
    }

    public function showReset(Request $request): Response
    {
        $token = (string) $request->attribute('token');
        $email = strtolower(trim((string) $request->query('email', '')));
        if (! $this->resets->isValid($email, $token)) {
            return Response::html($this->views->render('auth/reset-link-invalid.twig'), 410);
        }

        return Response::html($this->views->render('auth/reset-password.twig', [
            'csrf_token' => $this->csrf->token(),
            'token' => $token,
            'email' => $email,
            'error' => null,
        ]));
    }

    public function reset(Request $request): Response
    {
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $token = (string) $request->attribute('token');
        $error = ! filter_var($email, FILTER_VALIDATE_EMAIL)
            ? 'Enter a valid email address.'
            : $this->passwords->validate($request->input('password'), $request->input('password_confirmation'));

        if ($error === null) {
            try {
                $this->resets->reset($email, $token, (string) $request->input('password'));
                $this->csrf->rotate();

                return Response::html($this->views->render('auth/password-reset-complete.twig'));
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return Response::html($this->views->render('auth/reset-password.twig', [
            'csrf_token' => $this->csrf->token(),
            'token' => $token,
            'email' => $email,
            'error' => $error,
        ]), 422);
    }
}
