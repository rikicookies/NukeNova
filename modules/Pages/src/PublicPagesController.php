<?php

declare(strict_types=1);

namespace Modules\Pages\src;

use Modules\Comments\src\CommentService;
use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use Twig\Markup;

final class PublicPagesController
{
    public function __construct(
        private readonly PageRepository $pages, private readonly AuthManager $auth,
        private readonly SessionManager $session, private readonly ViewRenderer $views,
        private readonly EventDispatcher $events, private readonly ?CommentService $comments = null,
        private readonly ?CsrfTokenManager $csrf = null,
    ) {
    }

    public function index(): Response
    {
        $user = $this->auth->user();
        return Response::html($this->views->render('@pages/index.twig', [
            'pages' => $this->pages->directory($user ? (int) $user['id'] : null),
        ]));
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->attribute('slug');
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) return Response::html('Page not found.', 404);
        $page = $this->pages->publicPage($slug);
        if ($page === null) return Response::html('Page not found.', 404);
        $user = $this->auth->user();
        if (! $this->pages->canView($page, $user ? (int) $user['id'] : null)) return $user === null ? Response::redirect('/login') : Response::html('Forbidden', 403);
        if ($page['parent_id'] !== null && ($page['parent_title'] === null || ! $this->pages->canView([
            'id' => $page['parent_id'], 'access_type' => $page['parent_access_type'],
        ], $user ? (int) $user['id'] : null))) {
            $page['parent_title'] = null; $page['parent_slug'] = null;
        }
        $page['content_html'] = new Markup((string) $page['content'], 'UTF-8');
        $rendering = new PageRendering($page);
        $this->events->dispatch('page.rendering', $rendering);
        $page = $rendering->page;
        $template = in_array($page['template'] ?? null, ['default', 'landing'], true) ? (string) $page['template'] : 'default';
        $data = ['page' => $page, 'comments_available' => false];
        if ($this->comments !== null && $this->csrf !== null && (int) $page['comments_enabled'] === 1) {
            $data += [
                'comments_available' => true, 'comments' => $this->comments->for('pages', (int) $page['id']),
                'comments_guests_allowed' => $this->comments->guestsAllowed(), 'comments_csrf_token' => $this->csrf->token(),
                'comments_return_to' => '/pages/' . $page['slug'], 'comments_message' => $this->session->pull('comments.message'),
                'comments_error' => $this->session->pull('comments.error'), 'comments_user' => $user,
            ];
        }
        return Response::html($this->views->render('@pages/' . $template . '.twig', $data));
    }
}
