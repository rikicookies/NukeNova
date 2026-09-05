<?php

declare(strict_types=1);

namespace NovaNuke\Auth;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\RateLimiter;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class AccountController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly ProfileRepository $profiles,
        private readonly ProfileInput $input,
        private readonly AvatarUploadValidator $avatarValidator,
        private readonly AvatarStorage $avatars,
        private readonly AccountPasswordService $passwords,
        private readonly RateLimiter $passwordThrottle,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
        private readonly SessionManager $session,
        private readonly ViewRenderer $views,
    ) {
    }

    public function edit(): Response
    {
        $user = $this->auth->user(); if ($user === null) return Response::redirect('/login');
        $profile = $this->profiles->byUserId((int) $user['id']);
        if ($profile === null) return Response::html('Profile not found.', 404);
        return $this->view($profile, [], null, $this->session->pull('account.message'));
    }

    public function update(Request $request): Response
    {
        $user = $this->auth->user(); if ($user === null) return Response::redirect('/login');
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        $result = $this->input->validate($request->allInput());
        $current = $this->profiles->byUserId((int) $user['id']) ?? [];
        if ($result['errors'] !== []) return $this->view(array_replace($current, $result['data']), $result['errors'], null, null, 422);
        $result['data']['preferences'] = array_replace((array) ($current['preferences'] ?? []), $result['data']['preferences']);
        $this->profiles->update((int) $user['id'], $result['data']);
        $this->activity->log((int) $user['id'], 'profile.updated', 'user', $user['id'], [], $request->ip());
        $this->session->put('account.message', 'Profile preferences saved.');
        return Response::redirect('/account/profile', 303);
    }

    public function avatar(Request $request): Response
    {
        $user = $this->auth->user(); if ($user === null) return Response::redirect('/login');
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        $profile = $this->profiles->byUserId((int) $user['id']); if ($profile === null) return Response::html('Profile not found.', 404);
        try {
            $validated = $this->avatarValidator->validate($request->file('avatar'));
            $path = $this->avatars->store($validated);
            try { $this->profiles->setAvatar((int) $user['id'], $path); }
            catch (\Throwable $error) { $this->avatars->remove($path); throw $error; }
            try { $this->avatars->remove($profile['avatar_path'] ?: null); }
            catch (RuntimeException $error) { error_log('Previous avatar cleanup failed: ' . $error->getMessage()); }
            $this->activity->log((int) $user['id'], 'profile.avatar.updated', 'user', $user['id'], [], $request->ip());
            $this->session->put('account.message', 'Avatar updated.');
            return Response::redirect('/account/profile', 303);
        } catch (RuntimeException $error) {
            return $this->view($profile, [], $error->getMessage(), null, 422);
        }
    }

    public function removeAvatar(Request $request): Response
    {
        $user = $this->auth->user(); if ($user === null) return Response::redirect('/login');
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        $profile = $this->profiles->byUserId((int) $user['id']); if ($profile === null) return Response::html('Profile not found.', 404);
        $this->profiles->setAvatar((int) $user['id'], null);
        try { $this->avatars->remove($profile['avatar_path'] ?: null); }
        catch (RuntimeException $error) { error_log('Avatar cleanup failed: ' . $error->getMessage()); }
        $this->activity->log((int) $user['id'], 'profile.avatar.removed', 'user', $user['id'], [], $request->ip());
        $this->session->put('account.message', 'Avatar removed.');
        return Response::redirect('/account/profile', 303);
    }

    public function password(Request $request): Response
    {
        $user = $this->auth->user(); if ($user === null) return Response::redirect('/login');
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        $key = (string) $user['id'];
        if ($this->passwordThrottle->tooManyAttempts($key)) return $this->view($this->profiles->byUserId((int) $user['id']) ?? [], [], null, null, 429, 'Try changing the password again later.');
        $error = $this->passwords->change((int) $user['id'], $request->input('current_password'), $request->input('password'), $request->input('password_confirmation'));
        if ($error !== null) {
            $this->passwordThrottle->hit($key);
            return $this->view($this->profiles->byUserId((int) $user['id']) ?? [], [], null, null, 422, $error);
        }
        $this->passwordThrottle->clear($key);
        $this->activity->log((int) $user['id'], 'account.password.changed', 'user', $user['id'], [], $request->ip());
        $this->auth->logout(); $this->csrf->rotate();
        return Response::redirect('/login?password_changed=1', 303);
    }

    /** @param array<string,mixed> $profile @param array<string,string> $errors */
    private function view(array $profile, array $errors, ?string $avatarError, mixed $message, int $status = 200, ?string $passwordError = null): Response
    {
        $user = $this->auth->user() ?? [];
        $username = (string) ($profile['username'] ?? $user['username'] ?? '');
        $profile = array_replace([
            'username' => $username,
            'display_name' => $username,
            'avatar_path' => null,
            'bio' => null,
            'locale' => 'en',
            'timezone' => 'UTC',
            'preferences' => [],
            'profile_visibility' => 'public',
        ], $profile);
        return Response::html($this->views->render('auth/profile-edit.twig', [
            'profile' => $profile, 'errors' => $errors, 'avatar_error' => $avatarError,
            'message' => is_string($message) ? $message : null, 'password_error' => $passwordError,
            'csrf_token' => $this->csrf->token(),
            'timezones' => timezone_identifiers_list(),
        ]), $status);
    }
}
