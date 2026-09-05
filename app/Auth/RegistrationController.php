<?php

declare(strict_types=1);

namespace NovaNuke\Auth;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Core\Security\RateLimiter;
use RuntimeException;

final class RegistrationController
{
    public function __construct(
        private readonly RegistrationService $registration,
        private readonly RegistrationValidator $validator,
        private readonly RateLimiter $throttle,
        private readonly RateLimiter $resendThrottle,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
        private readonly string $locale,
        private readonly string $timezone,
    ) {
    }

    public function show(): Response
    {
        if (! $this->registration->isOpen()) {
            return Response::html($this->views->render('auth/registration-closed.twig'), 403);
        }

        return $this->form([], []);
    }

    public function register(Request $request): Response
    {
        if (! $this->registration->isOpen()) {
            return Response::html($this->views->render('auth/registration-closed.twig'), 403);
        }
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $input = $request->allInput();
        $errors = $this->validator->validate($input);
        $key = hash('sha256', 'register|' . $request->ip());
        if ($this->throttle->tooManyAttempts($key)) {
            $errors['register'] = 'Too many registration attempts. Try again later.';
        }

        if ($errors === []) {
            try {
                $verification = $this->registration->register(
                    trim((string) $input['username']),
                    strtolower(trim((string) $input['email'])),
                    (string) $input['password'],
                    $this->locale,
                    $this->timezone,
                );
                $this->throttle->hit($key);
                $this->csrf->rotate();

                return Response::html($this->views->render('auth/registration-complete.twig', [
                    'verification_required' => $verification,
                ]));
            } catch (RuntimeException $error) {
                $errors['register'] = $error->getMessage();
            }
        }

        unset($input['password'], $input['password_confirmation'], $input['_token']);
        return $this->form($errors, $input, 422);
    }

    public function verify(Request $request): Response
    {
        $verified = $this->registration->verify((string) $request->attribute('token'));

        return Response::html($this->views->render('auth/email-verification.twig', [
            'verified' => $verified,
        ]), $verified ? 200 : 410);
    }

    public function resendForm(): Response
    {
        return $this->resendView();
    }

    public function resend(Request $request): Response
    {
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }
        $result = (new VerificationResendInput())->validate($request->input('email'));
        $key = hash('sha256', 'verification-resend|' . $result['email'] . '|' . $request->ip());
        if ($result['error'] !== null) return $this->resendView($result['email'], $result['error'], false, 422);
        if ($this->resendThrottle->tooManyAttempts($key)) {
            return $this->resendView($result['email'], 'Too many requests. Try again later.', false, 429);
        }
        $this->resendThrottle->hit($key);
        $this->registration->resendVerification($result['email']);
        return $this->resendView('', null, true);
    }

    /** @param array<string, string> $errors
     *  @param array<string, mixed> $old
     */
    private function form(array $errors, array $old, int $status = 200): Response
    {
        return Response::html($this->views->render('auth/register.twig', [
            'csrf_token' => $this->csrf->token(),
            'errors' => $errors,
            'old' => $old,
            'verification_required' => $this->registration->verificationRequired(),
        ]), $status);
    }

    private function resendView(string $email = '', ?string $error = null, bool $sent = false, int $status = 200): Response
    {
        return Response::html($this->views->render('auth/resend-verification.twig', [
            'email' => $email, 'error' => $error, 'sent' => $sent, 'csrf_token' => $this->csrf->token(),
        ]), $status);
    }
}
