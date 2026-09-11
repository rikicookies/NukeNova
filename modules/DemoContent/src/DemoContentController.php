<?php

declare(strict_types=1);

namespace Modules\DemoContent\src;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class DemoContentController
{
    public function __construct(
        private readonly DemoContentInstaller $installer,
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
        if ($guard = $this->guard()) return $guard;
        return $this->view();
    }

    public function install(Request $request): Response
    {
        if ($guard = $this->guard()) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        if ($request->input('confirm_install') !== '1') return $this->view('Confirm installation of fictional demonstration content.', 422);
        $actor = $this->auth->user();
        try {
            $result = $this->installer->install((int) $actor['id']);
            $this->activity->log((int) $actor['id'], 'demo-content.installed', 'demo_dataset', DemoContentInstaller::DATASET, [
                'dataset' => DemoContentInstaller::DATASET,
                'items' => array_sum($result['counts']),
                'modules' => implode(',', $result['modules']),
            ], $request->ip());
            $this->session->put('demo-content.message', 'NovaTech Community demo content installed.');
            return Response::redirect('/admin/system/demo-content', 303);
        } catch (RuntimeException $error) {
            return $this->view($error->getMessage(), 422);
        }
    }

    private function view(?string $error = null, int $status = 200): Response
    {
        return Response::html($this->views->render('@demo-content/admin/index.twig', [
            'dataset' => DemoContentInstaller::DATASET,
            'state' => $this->installer->status(),
            'available_modules' => $this->installer->availableModules(),
            'csrf_token' => $this->csrf->token(),
            'message' => $this->session->pull('demo-content.message'),
            'error' => $error,
            'demo_password' => NovaTechCommunityDataset::PASSWORD,
        ]), $status);
    }

    private function guard(): ?Response
    {
        $user = $this->auth->user();
        if ($user === null) return Response::redirect('/login');
        if (! $this->authorization->allows((int) $user['id'], 'settings.manage')) return Response::html('Forbidden', 403);
        return $this->auth->isSuperAdministrator((int) $user['id']) ? null : Response::html('Forbidden', 403);
    }
}
