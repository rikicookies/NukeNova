<?php

declare(strict_types=1);

namespace Modules\News\src;

use Modules\Media\src\MediaRepository;
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

final class AdminNewsController
{
    public function __construct(
        private readonly NewsRepository $news,
        private readonly NewsInput $input,
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly ActivityLogger $activity,
        private readonly EventDispatcher $events,
        private readonly CsrfTokenManager $csrf,
        private readonly SessionManager $session,
        private readonly ViewRenderer $views,
        private readonly ?MediaRepository $media = null,
    ) {
    }

    public function index(): Response
    {
        if ($guard = $this->guard('news.edit')) return $guard;
        $message = $this->session->pull('news.message');
        return Response::html($this->views->render('@news/admin/index.twig', [
            'articles' => $this->news->adminArticles(), 'categories' => $this->news->categories(),
            'topics' => $this->news->topics(), 'csrf_token' => $this->csrf->token(),
            'message' => is_string($message) ? $message : null,
        ]));
    }

    public function create(): Response
    {
        if ($guard = $this->guard('news.edit')) return $guard;
        return $this->editor(null);
    }

    public function edit(Request $request): Response
    {
        if ($guard = $this->guard('news.edit')) return $guard;
        try {
            $article = $this->news->article($this->routeId($request));
            return $article === null ? Response::html('News article not found.', 404) : $this->editor($article);
        } catch (RuntimeException) {
            return Response::html('News article not found.', 404);
        }
    }

    public function save(Request $request): Response
    {
        if ($guard = $this->guard('news.edit')) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        try {
            $actor = $this->auth->user();
            $id = filter_var($request->input('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
            $data = $this->input->article($request->allInput(), $this->authorization->allows((int) $actor['id'], 'news.publish'));
            $articleId = $this->news->save($id ? (int) $id : null, $data, (int) $actor['id']);
            $action = $id ? 'content.updated' : 'content.created';
            $event = new ContentChanged('news', $articleId, (int) $actor['id']);
            $this->events->dispatch($action, $event);
            $this->activity->log((int) $actor['id'], "news.article." . ($id ? 'updated' : 'created'), 'news', $articleId, ['status' => $data['status']], $request->ip());
            $this->session->put('news.message', 'News article saved.');
            return Response::redirect('/admin/news', 303);
        } catch (RuntimeException $error) {
            return $this->editor($request->allInput(), $error->getMessage(), 422);
        }
    }

    public function delete(Request $request): Response
    {
        if ($guard = $this->guard('news.edit')) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        if ($request->input('confirm_delete') !== '1') return Response::html('Confirm deletion.', 422);
        try {
            $id = $this->routeId($request);
            $this->news->delete($id);
            $actor = $this->auth->user();
            $this->activity->log((int) $actor['id'], 'news.article.deleted', 'news', $id, [], $request->ip());
            $this->session->put('news.message', 'News article moved to deleted state.');
            return Response::redirect('/admin/news', 303);
        } catch (RuntimeException $error) {
            return Response::html(htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 422);
        }
    }

    public function taxonomy(Request $request): Response
    {
        if ($guard = $this->guard('news.edit')) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        try {
            $type = (string) $request->attribute('type');
            $id = $this->news->saveTaxonomy($type, $this->input->taxonomy($request->allInput()));
            $actor = $this->auth->user();
            $this->activity->log((int) $actor['id'], "news.{$type}.created", $type, $id, [], $request->ip());
            $this->session->put('news.message', ucfirst($type) . ' created.');
            return Response::redirect('/admin/news', 303);
        } catch (RuntimeException $error) {
            return Response::html(htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 422);
        }
    }

    private function editor(?array $article, ?string $error = null, int $status = 200): Response
    {
        return Response::html($this->views->render('@news/admin/edit.twig', [
            'article' => $article ?? [], 'categories' => $this->news->categories(), 'topics' => $this->news->topics(),
            'can_publish' => $this->authorization->allows((int) $this->auth->user()['id'], 'news.publish'),
            'csrf_token' => $this->csrf->token(), 'error' => $error,
            'media_available' => $this->media !== null, 'media_images' => $this->media?->all() ?? [],
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
        if ($id === false) throw new RuntimeException('Invalid news identifier.');
        return (int) $id;
    }
}
