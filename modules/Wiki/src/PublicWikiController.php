<?php

declare(strict_types=1);

namespace Modules\Wiki\src;

use Modules\Comments\src\CommentService;
use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Content\ContentFormat;
use NovaNuke\Core\Content\ContentProfile;
use NovaNuke\Core\Content\ContentRendererInterface;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;
use Twig\Markup;

final class PublicWikiController
{
    public function __construct(
        private readonly WikiRepository $pages,
        private readonly WikiInput $input,
        private readonly WikiNavigation $navigation,
        private readonly WikiLinkPresenter $linkPresenter,
        private readonly WikiAttachmentManager $attachments,
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly ContentRendererInterface $contentRenderer,
        private readonly ViewRenderer $views,
        private readonly SessionManager $session,
        private readonly CsrfTokenManager $csrf,
        private readonly ?CommentService $comments = null,
    ) {
    }

    public function index(Request $request): Response
    {
        try {
            $namespace = $this->input->namespace($request->query('namespace', ''));
        } catch (RuntimeException) {
            return Response::html('Wiki namespace not found.', 404);
        }
        $user = $this->auth->user();
        $directory = $this->navigation->directory(
            $this->pages->directory($user ? (int) $user['id'] : null),
            $namespace,
        );
        if ($namespace !== '' && $directory['pages'] === [] && $directory['namespaces'] === []) {
            return Response::html('Wiki namespace not found.', 404);
        }
        return Response::html($this->views->render('@wiki/index.twig', [
            'namespace' => $namespace,
            'pages' => $directory['pages'],
            'namespaces' => $directory['namespaces'],
            'breadcrumbs' => $directory['breadcrumbs'],
            'can_create' => $user !== null && $this->authorization->allows((int) $user['id'], 'wiki.edit'),
        ]));
    }

    public function recent(): Response
    {
        $user = $this->auth->user();
        return Response::html($this->views->render('@wiki/recent.twig', [
            'pages' => $this->pages->recentChanges($user ? (int) $user['id'] : null),
        ]));
    }

    public function map(): Response
    {
        $user = $this->auth->user();
        return Response::html($this->views->render('@wiki/map.twig', [
            'map' => $this->navigation->sitemap($this->pages->directory($user ? (int) $user['id'] : null)),
        ]));
    }

    public function search(Request $request): Response
    {
        $rawTerm = $request->query('q', '');
        $term = is_string($rawTerm) ? trim($rawTerm) : '';
        $results = [];
        $error = null;
        if ($rawTerm !== '') {
            try {
                $term = $this->input->searchTerm($rawTerm);
                $user = $this->auth->user();
                $results = $this->pages->search($term, $user ? (int) $user['id'] : null);
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }
        return Response::html($this->views->render('@wiki/search.twig', [
            'term' => $term,
            'results' => $results,
            'error' => $error,
            'searched' => $term !== '' && $error === null,
        ]), $error === null ? 200 : 422);
    }

    public function show(Request $request): Response
    {
        try {
            $path = $this->input->path($request->attribute('path'));
        } catch (RuntimeException) {
            return Response::html('Wiki page not found.', 404);
        }
        $user = $this->auth->user();
        $canEdit = $user !== null && $this->authorization->allows((int) $user['id'], 'wiki.edit');
        $page = $this->pages->publishedByPath($path);
        if ($page === null) {
            $draft = $canEdit ? $this->pages->byPath($path) : null;
            return Response::html($this->views->render('@wiki/missing.twig', [
                'path' => $path,
                'action_url' => $draft !== null
                    ? '/admin/wiki/' . (int) $draft['id'] . '/edit'
                    : ($canEdit ? '/admin/wiki/new?path=' . rawurlencode($path) : null),
                'draft_exists' => $draft !== null,
            ]), 404);
        }
        if (! $this->pages->canView($page, $user ? (int) $user['id'] : null)) {
            return $user === null ? Response::redirect('/login') : Response::html('This wiki page is not available for your account.', 403);
        }
        $visiblePages = $this->pages->directory($user ? (int) $user['id'] : null);
        $visiblePaths = array_map(
            static fn (array $item): string => ($item['namespace'] === '' ? '' : $item['namespace'] . ':') . $item['slug'],
            $visiblePages,
        );
        $page['content_html'] = new Markup($this->linkPresenter->markMissing($this->contentRenderer->render(
            (string) $page['content'], ContentFormat::Markdown, ContentProfile::FullContent,
        ), $visiblePaths), 'UTF-8');
        $commentData = ['comments_available' => false];
        if ($this->comments !== null && (int) ($page['comments_enabled'] ?? 0) === 1) {
            $commentData = [
                'comments_available' => true,
                'comments' => $this->comments->for('wiki', (int) $page['id']),
                'comments_guests_allowed' => $this->comments->guestsAllowed(),
                'comments_csrf_token' => $this->csrf->token(),
                'comments_return_to' => '/wiki/' . $page['path'],
                'comments_message' => $this->session->pull('comments.message'),
                'comments_error' => $this->session->pull('comments.error'),
                'comments_user' => $user,
            ];
        }
        return Response::html($this->views->render('@wiki/show.twig', [
            'page' => $page,
            'edit_url' => $canEdit ? '/admin/wiki/' . (int) $page['id'] . '/edit' : null,
            'backlinks' => $this->pages->backlinks($path, $user ? (int) $user['id'] : null),
            'breadcrumbs' => $this->navigation->pageBreadcrumbs($path, (string) $page['title']),
            'attachments' => $this->attachments->forPage((int) $page['id']),
        ] + $commentData));
    }

    public function attachment(Request $request): Response
    {
        $id = filter_var($request->attribute('attachment'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) return Response::html('Wiki attachment not found.', 404);

        $user = $this->auth->user();
        $canEdit = $user !== null && $this->authorization->allows((int) $user['id'], 'wiki.edit');
        $attachment = $this->attachments->find((int) $id);
        if ($attachment === null || $attachment['deleted_at'] !== null) return Response::html('Wiki attachment not found.', 404);
        $published = $attachment['status'] === 'published' && $attachment['published_at'] !== null
            && (string) $attachment['published_at'] <= gmdate('Y-m-d H:i:s');
        if (! $published && ! $canEdit) return Response::html('Wiki attachment not found.', 404);
        if ($published && ! $canEdit && ! $this->pages->canView($attachment, $user ? (int) $user['id'] : null)) {
            return $user === null ? Response::redirect('/login') : Response::html('This Wiki attachment is not available for your account.', 403);
        }

        try {
            $path = $this->attachments->path((string) $attachment['stored_name']);
            if ($request->query('inline') === '1' && in_array((string) $attachment['mime_type'], ['image/png', 'image/jpeg', 'image/webp'], true)) {
                return new Response(static function () use ($path): void { readfile($path); }, 200, [
                    'Content-Type' => (string) $attachment['mime_type'],
                    'Content-Length' => (string) filesize($path),
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => 'private, no-store',
                ]);
            }
            return Response::download(
                $path,
                (string) $attachment['original_name'],
                (string) $attachment['mime_type'],
            );
        } catch (RuntimeException) {
            return Response::html('Wiki attachment file is unavailable.', 404);
        }
    }
}
