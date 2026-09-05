<?php

declare(strict_types=1);

namespace Modules\News\src;

use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;
use Modules\Comments\src\CommentService;
use NovaNuke\Auth\AuthManager;
use Twig\Markup;

final class PublicNewsController
{
    public function __construct(
        private readonly NewsRepository $news, private readonly SessionManager $session,
        private readonly ViewRenderer $views, private readonly ?CommentService $comments = null,
        private readonly ?CsrfTokenManager $csrf = null, private readonly ?AuthManager $auth = null,
    )
    {
    }

    public function index(Request $request, ?string $category = null): Response
    {
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        return Response::html($this->views->render('@news/index.twig', [
            'result' => $this->news->publicArticles((int) $page, $category),
            'categories' => $this->news->categories(), 'selected_category' => $category,
        ]));
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->attribute('slug');
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) return Response::html('News article not found.', 404);
        $article = $this->news->publicArticle($slug);
        if ($article === null) return Response::html('News article not found.', 404);
        $viewed = array_map('intval', (array) $this->session->get('news.viewed', []));
        if (! in_array((int) $article['id'], $viewed, true)) {
            $this->news->incrementViews((int) $article['id']);
            $viewed[] = (int) $article['id'];
            $this->session->put('news.viewed', array_slice(array_unique($viewed), -100));
            $article['view_count'] = (int) $article['view_count'] + 1;
        }
        $article['content_html'] = new Markup((string) $article['content'], 'UTF-8');
        $commentData = ['comments_available' => false];
        if ($this->comments !== null && $this->csrf !== null && (int) $article['comments_enabled'] === 1) {
            $commentData = [
                'comments_available' => true,
                'comments' => $this->comments->for('news', (int) $article['id']),
                'comments_guests_allowed' => $this->comments->guestsAllowed(),
                'comments_csrf_token' => $this->csrf->token(),
                'comments_return_to' => '/news/' . $article['slug'],
                'comments_message' => $this->session->pull('comments.message'),
                'comments_error' => $this->session->pull('comments.error'),
                'comments_user' => $this->auth?->user(),
            ];
        }
        return Response::html($this->views->render('@news/show.twig', ['article' => $article] + $commentData));
    }
}
