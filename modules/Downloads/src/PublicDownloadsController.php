<?php

declare(strict_types=1);

namespace Modules\Downloads\src;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Core\Content\ContentFormat;
use NovaNuke\Core\Content\ContentProfile;
use NovaNuke\Core\Content\ContentRendererInterface;
use RuntimeException;
use Twig\Markup;

final class PublicDownloadsController
{
    public function __construct(
        private readonly DownloadRepository $downloads, private readonly DownloadManager $manager,
        private readonly AuthManager $auth, private readonly EventDispatcher $events,
        private readonly CsrfTokenManager $csrf, private readonly SessionManager $session,
        private readonly ViewRenderer $views, private readonly AuthorizationService $authorization,
        private readonly ContentRendererInterface $contentRenderer,
    ) {
    }

    public function index(Request $request, ?string $category = null): Response
    {
        if ($category !== null && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $category)) return Response::html('Download category not found.', 404);
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $search = trim((string) $request->query('q', '')); if (mb_strlen($search) > 100) $search = mb_substr($search, 0, 100);
        $order = (string) $request->query('order', 'new'); if (! in_array($order, ['new', 'popular', 'name'], true)) $order = 'new';
        $user = $this->auth->user();
        $result = $this->downloads->catalog((int) $page, $category, $search, $order, $user ? (int) $user['id'] : null);
        foreach ($result['items'] as &$item) {
            $item['description_text'] = trim(strip_tags($this->contentRenderer->render(
                (string) $item['description'], ContentFormat::fromInput($item['description_format'] ?? null), ContentProfile::Description,
            )));
        }
        unset($item);
        return Response::html($this->views->render('@downloads/index.twig', [
            'result' => $result,
            'categories' => $this->downloads->categories(), 'selected_category' => $category, 'search' => $search, 'order' => $order,
        ]));
    }

    public function show(Request $request): Response
    {
        $slug = $this->slug($request->attribute('slug')); $download = $this->downloads->publicDownload($slug); if ($download === null) return Response::html('Download not found.', 404);
        $user = $this->auth->user(); if (! $this->downloads->canView($download, $user ? (int) $user['id'] : null)) return $user === null ? Response::redirect('/login') : Response::html('This download is not available for your account.', 403);
        $download['description_html'] = new Markup($this->contentRenderer->render(
            (string) $download['description'], ContentFormat::fromInput($download['description_format'] ?? null), ContentProfile::Description,
        ), 'UTF-8');
        $download['requirements_html'] = new Markup($this->contentRenderer->render(
            (string) ($download['requirements'] ?? ''), ContentFormat::fromInput($download['requirements_format'] ?? null), ContentProfile::Description,
        ), 'UTF-8');
        $editUrl = $user !== null && $this->authorization->allows((int) $user['id'], 'downloads.manage')
            ? '/admin/downloads/' . (int) $download['id'] . '/edit'
            : null;
        return Response::html($this->views->render('@downloads/show.twig', [
            'download' => $download, 'csrf_token' => $this->csrf->token(),
            'message' => $this->session->pull('downloads.message'), 'error' => $this->session->pull('downloads.error'),
            'edit_url' => $editUrl,
            'delete_url' => $editUrl !== null ? '/admin/downloads/' . (int) $download['id'] . '/delete' : null,
            'content_csrf_token' => $editUrl !== null ? $this->csrf->token() : null,
            'delete_return_to' => '/downloads',
        ]));
    }

    public function deliver(Request $request): Response
    {
        try {
            $prepared = $this->manager->prepare($this->slug($request->attribute('slug')), $request); $download = $prepared['download'];
            return $download['source_type'] === 'external'
                ? Response::externalRedirect((string) $download['external_url'], 302)
                : Response::download((string) $prepared['path'], (string) $download['original_name'], (string) $download['mime_type']);
        } catch (RuntimeException $error) {
            $message = htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return Response::html($message, str_contains($error->getMessage(), 'not found') ? 404 : 403);
        }
    }

    public function report(Request $request): Response
    {
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        try { $return = '/downloads/' . $this->slug($request->input('slug')); $this->manager->report($request, $this->id($request->attribute('id'))); $this->session->put('downloads.message', 'Broken download report submitted.'); }
        catch (RuntimeException $error) { $return = '/downloads'; $this->session->put('downloads.error', $error->getMessage()); }
        return Response::redirect($return, 303);
    }

    private function slug(mixed $value): string { $slug = (string) $value; if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) throw new RuntimeException('Download not found.'); return $slug; }
    private function id(mixed $value): int { $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($id === false) throw new RuntimeException('Invalid download identifier.'); return (int) $id; }
}
