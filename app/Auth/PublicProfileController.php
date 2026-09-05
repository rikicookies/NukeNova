<?php

declare(strict_types=1);

namespace NovaNuke\Auth;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class PublicProfileController
{
    public function __construct(
        private readonly ProfileRepository $profiles,
        private readonly AvatarStorage $avatars,
        private readonly AuthManager $auth,
        private readonly ViewRenderer $views,
    ) {
    }

    public function show(Request $request): Response
    {
        $username = (string) $request->attribute('username');
        if (! preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) return Response::html('Profile not found.', 404);
        $profile = $this->profiles->byUsername($username);
        if ($profile === null) return Response::html('Profile not found.', 404);
        $viewer = $this->auth->user();
        if ($profile['profile_visibility'] === 'members' && $viewer === null) return Response::html('Sign in to view this profile.', 403);
        return Response::html($this->views->render('auth/profile-public.twig', ['profile' => $profile, 'viewer' => $viewer]));
    }

    public function avatar(Request $request): Response
    {
        try { $avatar = $this->avatars->resolve((string) $request->attribute('filename')); }
        catch (RuntimeException) { return Response::html('Avatar not found.', 404); }
        return new Response(static function () use ($avatar): void { readfile($avatar['path']); }, 200, [
            'Content-Type' => $avatar['mime'], 'Content-Length' => (string) $avatar['size'],
            'Content-Disposition' => 'inline', 'Cache-Control' => 'public, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
