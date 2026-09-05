<?php

declare(strict_types=1);

namespace Modules\Pages\src;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class AdminPagesController
{
    public function __construct(
        private readonly PageRepository $pages, private readonly PageInput $input,
        private readonly AuthManager $auth, private readonly AuthorizationService $authorization,
        private readonly ActivityLogger $activity, private readonly EventDispatcher $events,
        private readonly CsrfTokenManager $csrf, private readonly SessionManager $session,
        private readonly ViewRenderer $views,
    ) {
    }

    public function index(): Response
    {
        if ($guard = $this->guard('pages.edit')) return $guard;
        return Response::html($this->views->render('@pages/admin/index.twig', [
            'pages' => $this->pages->adminPages(), 'csrf_token' => $this->csrf->token(),
            'message' => $this->session->pull('pages.message'),
        ]));
    }

    public function create(): Response
    {
        if ($guard = $this->guard('pages.edit')) return $guard;
        return $this->editor(null);
    }

    public function edit(Request $request): Response
    {
        if ($guard = $this->guard('pages.edit')) return $guard;
        try {
            $page = $this->pages->page($this->routeId($request));
            return $page === null ? Response::html('Page not found.', 404) : $this->editor($page);
        } catch (RuntimeException) { return Response::html('Page not found.', 404); }
    }

    public function save(Request $request): Response
    {
        if ($guard = $this->guard('pages.edit')) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        try {
            $actor = $this->auth->user();
            $id = filter_var($request->input('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $data = $this->input->page($request->allInput(), $this->authorization->allows((int) $actor['id'], 'pages.publish'));
            $pageId = $this->pages->save($id ? (int) $id : null, $data, (int) $actor['id']);
            $action = $id ? 'content.updated' : 'content.created';
            $this->events->dispatch($action, new PageChanged('pages', $pageId, (int) $actor['id']));
            $this->activity->log((int) $actor['id'], 'page.' . ($id ? 'updated' : 'created'), 'page', $pageId, ['status' => $data['status']], $request->ip());
            $this->session->put('pages.message', 'Page saved.');
            return Response::redirect('/admin/pages', 303);
        } catch (RuntimeException $error) { return $this->editor($request->allInput(), $error->getMessage(), 422); }
    }

    public function delete(Request $request): Response
    {
        if ($guard = $this->guard('pages.edit')) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        if ($request->input('confirm_delete') !== '1') return Response::html('Confirm deletion.', 422);
        try {
            $id = $this->routeId($request); $this->pages->delete($id);
            $actor = $this->auth->user();
            $this->activity->log((int) $actor['id'], 'page.deleted', 'page', $id, [], $request->ip());
            $this->session->put('pages.message', 'Page moved to deleted state.');
            return Response::redirect('/admin/pages', 303);
        } catch (RuntimeException $error) {
            return Response::html(htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 422);
        }
    }

    private function editor(?array $page, ?string $error = null, int $status = 200): Response
    {
        return Response::html($this->views->render('@pages/admin/edit.twig', [
            'page' => $page ?? [], 'parents' => $this->pages->parentOptions(isset($page['id']) ? (int) $page['id'] : null),
            'roles' => $this->pages->roles(), 'can_publish' => $this->authorization->allows((int) $this->auth->user()['id'], 'pages.publish'),
            'csrf_token' => $this->csrf->token(), 'error' => $error,
        ]), $status);
    }

    private function guard(string $permission): ?Response
    {
        $user = $this->auth->user();
        if ($user === null) return Response::redirect('/login');
        return $this->authorization->allows((int) $user['id'], $permission) ? null : Response::html('Forbidden', 403);
    }

    private function routeId(Request $request): int
    {
        $id = filter_var($request->attribute('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new RuntimeException('Invalid page identifier.');
        return (int) $id;
    }
}
