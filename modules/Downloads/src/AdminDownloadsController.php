<?php

declare(strict_types=1);

namespace Modules\Downloads\src;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class AdminDownloadsController
{
    public function __construct(
        private readonly DownloadRepository $downloads, private readonly DownloadManager $manager,
        private readonly DownloadInput $input, private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization, private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf, private readonly SessionManager $session,
        private readonly ViewRenderer $views,
    ) {
    }

    public function index(): Response
    {
        if ($guard = $this->guard()) return $guard;
        return Response::html($this->views->render('@downloads/admin/index.twig', [
            'downloads' => $this->downloads->adminDownloads(), 'categories' => $this->downloads->categories(),
            'reports' => $this->downloads->openReports(), 'csrf_token' => $this->csrf->token(),
            'message' => $this->session->pull('downloads.admin.message'), 'error' => $this->session->pull('downloads.admin.error'),
        ]));
    }

    public function create(): Response { if ($guard = $this->guard()) return $guard; return $this->editor(null); }

    public function edit(Request $request): Response
    {
        if ($guard = $this->guard()) return $guard;
        try { $download = $this->downloads->download($this->id($request->attribute('id'))); return $download === null ? Response::html('Download not found.', 404) : $this->editor($download); }
        catch (RuntimeException) { return Response::html('Download not found.', 404); }
    }

    public function save(Request $request): Response
    {
        if ($guard = $this->guard()) return $guard; if ($csrf = $this->csrf($request)) return $csrf;
        try {
            $actor = $this->auth->user(); $id = filter_var($request->input('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $data = $this->input->download($request->allInput(), $this->authorization->allows((int) $actor['id'], 'downloads.publish'));
            $downloadId = $this->manager->save($id ? (int) $id : null, $data, $request->file('download_file'), (int) $actor['id']);
            $this->activity->log((int) $actor['id'], 'download.' . ($id ? 'updated' : 'created'), 'download', $downloadId, ['status' => $data['status']], $request->ip());
            return $this->redirect('Download saved.');
        } catch (RuntimeException $error) { return $this->editor($request->allInput(), $error->getMessage(), 422); }
    }

    public function category(Request $request): Response
    {
        if ($guard = $this->guard()) return $guard; if ($csrf = $this->csrf($request)) return $csrf;
        try {
            $id = $this->downloads->saveCategory($this->input->category($request->allInput())); $actor = $this->auth->user();
            $this->activity->log((int) $actor['id'], 'download.category.created', 'download_category', $id, [], $request->ip()); return $this->redirect('Category created.');
        } catch (RuntimeException $error) { return $this->redirect(null, $error->getMessage()); }
    }

    public function delete(Request $request): Response
    {
        if ($guard = $this->guard()) return $guard; if ($csrf = $this->csrf($request)) return $csrf;
        if ($request->input('confirm_delete') !== '1') return $this->redirect(null, 'Confirm deletion.');
        try { $id = $this->id($request->attribute('id')); $this->downloads->delete($id); $actor = $this->auth->user(); $this->activity->log((int) $actor['id'], 'download.deleted', 'download', $id, [], $request->ip()); return $this->redirect('Download moved to deleted state.'); }
        catch (RuntimeException $error) { return $this->redirect(null, $error->getMessage()); }
    }

    public function resolve(Request $request): Response
    {
        if ($guard = $this->guard()) return $guard; if ($csrf = $this->csrf($request)) return $csrf;
        try { $id = $this->id($request->attribute('id')); $this->downloads->resolveReport($id); $actor = $this->auth->user(); $this->activity->log((int) $actor['id'], 'download.report.resolved', 'download_report', $id, [], $request->ip()); return $this->redirect('Report resolved.'); }
        catch (RuntimeException $error) { return $this->redirect(null, $error->getMessage()); }
    }

    private function editor(?array $download, ?string $error = null, int $status = 200): Response
    {
        return Response::html($this->views->render('@downloads/admin/edit.twig', [
            'download' => $download ?? [], 'categories' => $this->downloads->categories(), 'roles' => $this->downloads->roles(),
            'can_publish' => $this->authorization->allows((int) $this->auth->user()['id'], 'downloads.publish'),
            'csrf_token' => $this->csrf->token(), 'error' => $error,
        ]), $status);
    }

    private function guard(): ?Response { $user = $this->auth->user(); if ($user === null) return Response::redirect('/login'); return $this->authorization->allows((int) $user['id'], 'downloads.manage') ? null : Response::html('Forbidden', 403); }
    private function csrf(Request $request): ?Response { return $this->csrf->validate($request->input('_token')) ? null : Response::html('Invalid or expired CSRF token.', 419); }
    private function id(mixed $value): int { $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($id === false) throw new RuntimeException('Invalid identifier.'); return (int) $id; }
    private function redirect(?string $message = null, ?string $error = null): Response { if ($message !== null) $this->session->put('downloads.admin.message', $message); if ($error !== null) $this->session->put('downloads.admin.error', $error); return Response::redirect('/admin/downloads', 303); }
}
