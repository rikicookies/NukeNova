<?php

declare(strict_types=1);

namespace Modules\Comments\src;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use RuntimeException;

final class PublicCommentsController
{
    public function __construct(
        private readonly CommentService $comments, private readonly AuthManager $auth,
        private readonly ActivityLogger $activity, private readonly CsrfTokenManager $csrf,
        private readonly SessionManager $session,
    ) {
    }

    public function create(Request $request): Response
    {
        return $this->perform($request, function () use ($request): array {
            $type = (string) $request->attribute('type');
            $contentId = $this->id($request->attribute('id'));
            $id = $this->comments->create($request, $type, $contentId);
            return [$id, 'comment.created', 'Comment submitted successfully.'];
        });
    }

    public function edit(Request $request): Response
    {
        return $this->perform($request, function () use ($request): array {
            $id = $this->id($request->attribute('id'));
            $this->comments->edit($request, $id);
            return [$id, 'comment.edited', 'Comment updated.'];
        });
    }

    public function report(Request $request): Response
    {
        return $this->perform($request, function () use ($request): array {
            $commentId = $this->id($request->attribute('id'));
            $reportId = $this->comments->report($request, $commentId);
            return [$reportId, 'comment.reported', 'Report submitted.'];
        });
    }

    private function perform(Request $request, callable $operation): Response
    {
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);
        $returnTo = $this->returnTo($request->input('return_to'));
        try {
            [$id, $action, $message] = $operation();
            $user = $this->auth->user();
            $this->activity->log($user ? (int) $user['id'] : null, $action, 'comment', $id, [], $request->ip());
            $this->session->put('comments.message', $message);
            return Response::redirect($returnTo, 303);
        } catch (RuntimeException $error) {
            $this->session->put('comments.error', $error->getMessage());
            return Response::redirect($returnTo, 303);
        }
    }

    private function id(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new RuntimeException('Invalid comment identifier.');
        return (int) $id;
    }

    private function returnTo(mixed $value): string
    {
        $value = (string) $value;
        return str_starts_with($value, '/') && ! str_starts_with($value, '//') && ! preg_match('/[\x00-\x1F]/', $value) ? $value : '/';
    }
}
