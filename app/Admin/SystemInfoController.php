<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Response;
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
    ) {
    }

    public function index(): Response
    {
        $user = $this->auth->user();
        if ($user === null) return Response::redirect('/login');
        if (! $this->authorization->allows((int) $user['id'], 'settings.manage')) {
            return Response::html('Forbidden', 403);
        }

        return Response::html($this->views->render('admin/system.twig', [
            'system' => $this->inspector->inspect(),
        ]));
    }
}
