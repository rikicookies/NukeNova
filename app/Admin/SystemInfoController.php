<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\System\SystemInspector;
use NovaNuke\Core\View\ViewRenderer;

final class SystemInfoController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly SystemInspector $inspector,
        private readonly ViewRenderer $views,
        private readonly SettingsRepository $settings,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) return Response::redirect('/login');
        if (! $this->authorization->allows((int) $user['id'], 'settings.manage')) {
            return Response::html('Forbidden', 403);
        }

        return Response::html($this->views->render('admin/system.twig', [
            'system' => $this->inspector->inspect(),
            'maintenance' => $this->settings->boolean('system.maintenance', false),
            'csrf_token' => $this->csrf->token(),
            'saved' => $request->query('saved') === '1',
        ]));
    }

    public function update(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) return Response::redirect('/login');
        if (! $this->authorization->allows((int) $user['id'], 'settings.manage')) return Response::html('Forbidden', 403);
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);

        $enabled = $request->input('maintenance') === '1';
        $this->settings->setBoolean('system.maintenance', $enabled, 'system');
        $this->activity->log((int) $user['id'], 'system.maintenance.updated', 'settings', 'system.maintenance', [
            'enabled' => $enabled,
        ], $request->ip());

        return Response::redirect('/admin/system?saved=1', 303);
    }
}
