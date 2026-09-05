<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Menus\MenuManager;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class MenusController
{
    public function __construct(
        private readonly MenuManager $menus,
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
        $guard = $this->guard();
        if ($guard !== null) {
            return $guard;
        }
        $message = $this->session->pull('menus.message');
        return $this->view(is_string($message) ? $message : null);
    }

    public function saveMenu(Request $request): Response
    {
        return $this->mutate($request, function () use ($request): array {
            $id = $this->menus->saveMenu($request->allInput());
            return ['menu.saved', 'menu', $id, 'Menu saved successfully.'];
        });
    }

    public function saveItem(Request $request): Response
    {
        return $this->mutate($request, function () use ($request): array {
            $id = $this->menus->saveItem($request->allInput());
            return ['menu_item.saved', 'menu_item', $id, 'Menu item saved successfully.'];
        });
    }

    public function deleteMenu(Request $request): Response
    {
        return $this->mutate($request, function () use ($request): array {
            if ($request->input('confirm_delete') !== '1') {
                throw new RuntimeException('Confirm menu deletion before continuing.');
            }
            $id = $this->routeId($request);
            $this->menus->deleteMenu($id);
            return ['menu.deleted', 'menu', $id, 'Menu deleted.'];
        });
    }

    public function deleteItem(Request $request): Response
    {
        return $this->mutate($request, function () use ($request): array {
            if ($request->input('confirm_delete') !== '1') {
                throw new RuntimeException('Confirm item deletion before continuing.');
            }
            $id = $this->routeId($request);
            $this->menus->deleteItem($id);
            return ['menu_item.deleted', 'menu_item', $id, 'Menu item and its children were deleted.'];
        });
    }

    /** @param callable(): array{string,string,int,string} $operation */
    private function mutate(Request $request, callable $operation): Response
    {
        $guard = $this->guard();
        if ($guard !== null) {
            return $guard;
        }
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }
        try {
            [$action, $type, $id, $message] = $operation();
            $actor = $this->auth->user();
            $this->activity->log((int) $actor['id'], $action, $type, $id, [], $request->ip());
            $this->session->put('menus.message', $message);
            return Response::redirect('/admin/menus', 303);
        } catch (RuntimeException $error) {
            return $this->view(null, $error->getMessage(), 422);
        }
    }

    private function routeId(Request $request): int
    {
        $id = filter_var($request->attribute('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new RuntimeException('Invalid identifier.');
        }
        return (int) $id;
    }

    private function guard(): ?Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }
        return $this->authorization->allows((int) $user['id'], 'menus.manage')
            ? null : Response::html('Forbidden', 403);
    }

    private function view(?string $message = null, ?string $error = null, int $status = 200): Response
    {
        return Response::html($this->views->render('admin/menus/index.twig', [
            'menu_list' => $this->menus->all(),
            'roles' => $this->menus->roles(),
            'csrf_token' => $this->csrf->token(),
            'message' => $message,
            'error' => $error,
        ]), $status);
    }
}
