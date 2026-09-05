<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Modules\ModuleManager;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class ModulesController
{
    public function __construct(
        private readonly ModuleManager $modules,
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
    ) {
    }

    public function index(): Response
    {
        $guard = $this->guard();
        if ($guard !== null) {
            return $guard;
        }

        return $this->view();
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
        $allowed = ['install', 'update', 'enable', 'disable', 'uninstall'];
        if (! in_array($action, $allowed, true)) {
            return Response::html('Unsupported module action.', 404);
        }

        try {
            if ($action === 'uninstall' && ! hash_equals($slug, (string) $request->input('confirm_slug'))) {
                throw new RuntimeException("Type the module slug ({$slug}) to confirm uninstallation.");
            }
            switch ($action) {
                case 'install': $this->modules->install($slug); break;
                case 'update': $this->modules->update($slug); break;
                case 'enable': $this->modules->enable($slug); break;
                case 'disable': $this->modules->disable($slug); break;
                case 'uninstall':
                    $this->modules->uninstall($slug, $request->input('delete_data') === '1');
                    break;
            }
            $actor = $this->auth->user();
            $this->activity->log((int) $actor['id'], "module.{$action}", 'module', $slug, [
                'delete_data' => $action === 'uninstall' && $request->input('delete_data') === '1',
            ], $request->ip());
            $this->csrf->rotate();

            return $this->view("Module action completed: {$action}.");
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

        return $this->authorization->allows((int) $user['id'], 'modules.manage')
            ? null
            : Response::html('Forbidden', 403);
    }

    private function view(?string $message = null, ?string $error = null, int $status = 200): Response
    {
        return Response::html($this->views->render('admin/modules/index.twig', [
            'modules' => $this->modules->inventory(),
            'csrf_token' => $this->csrf->token(),
            'message' => $message,
            'error' => $error,
        ]), $status);
    }
}
