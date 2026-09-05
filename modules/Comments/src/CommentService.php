<?php

declare(strict_types=1);

namespace Modules\Comments\src;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Security\DatabaseRateLimiter;
use NovaNuke\Core\Settings\SettingsRepository;
use RuntimeException;

final class CommentService
{
    public function __construct(
        private readonly CommentRepository $repository,
        private readonly CommentTreeBuilder $trees,
        private readonly AuthManager $auth,
        private readonly SettingsRepository $settings,
        private readonly EventDispatcher $events,
        private readonly DatabaseRateLimiter $limiter,
        private readonly string $appKey,
    ) {
    }

    public function for(string $type, int $id): array { return $this->trees->build($this->repository->approved($type, $id)); }
    public function guestsAllowed(): bool { return $this->settings->boolean('comments.guests_allowed', false); }
    public function moderationRequired(): bool { return $this->settings->boolean('comments.moderation_required', true); }

    public function create(Request $request, string $type, int $contentId): int
    {
        if (! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $type) || $contentId < 1) throw new RuntimeException('Invalid comment target.');
        $target = new CommentTargetChecking($type, $contentId);
        $this->events->dispatch('comments.content.checking', $target);
        if (! $target->accepted) throw new RuntimeException('This content does not accept comments.');
        $user = $this->auth->user();
        if ($user === null && ! $this->guestsAllowed()) throw new RuntimeException('Sign in to comment.');
        $key = ($user ? 'user:' . $user['id'] : 'ip:' . $request->ip()) . '|' . $type . ':' . $contentId;
        if ($this->limiter->tooManyAttempts($key)) throw new RuntimeException('Too many comments. Please wait before trying again.');
        $body = $this->body($request->input('body'));
        $guestName = null;
        if ($user === null) {
            $guestName = trim((string) $request->input('guest_name'));
            if (mb_strlen($guestName) < 2 || mb_strlen($guestName) > 100) throw new RuntimeException('Guest name must contain 2-100 characters.');
        }
        $parent = filter_var($request->input('parent_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $status = $this->moderationRequired() ? 'pending' : 'approved';
        $id = $this->repository->create([
            'content_type' => $type, 'content_id' => $contentId, 'parent_id' => $parent ? (int) $parent : null,
            'user_id' => $user ? (int) $user['id'] : null, 'guest_name' => $guestName, 'body' => $body,
            'status' => $status, 'ip_hash' => $this->hash($request->ip()),
        ]);
        $this->limiter->hit($key);
        $this->events->dispatch('comment.created', new CommentCreated($id, $type, $contentId, $status));
        return $id;
    }

    public function edit(Request $request, int $id): void
    {
        $user = $this->auth->user();
        if ($user === null) throw new RuntimeException('Sign in to edit comments.');
        $this->repository->edit($id, (int) $user['id'], $this->body($request->input('body')));
    }

    public function report(Request $request, int $id): int
    {
        $reason = trim((string) $request->input('reason'));
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) throw new RuntimeException('Report reason must contain 5-500 characters.');
        $user = $this->auth->user();
        $identity = $user ? 'user:' . $user['id'] : 'ip:' . $request->ip();
        $key = 'report|' . $identity;
        if ($this->limiter->tooManyAttempts($key)) throw new RuntimeException('Too many reports. Please wait before trying again.');
        $report = $this->repository->report($id, $user ? (int) $user['id'] : null, $this->hash($identity), $reason);
        $this->limiter->hit($key);
        return $report;
    }

    private function body(mixed $value): string
    {
        $body = trim(strip_tags((string) $value));
        if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) throw new RuntimeException('Comment must contain 2-5000 characters.');
        return $body;
    }

    private function hash(string $value): string
    {
        if ($this->appKey === '') throw new RuntimeException('APP_KEY is required for comment identity protection.');
        return hash_hmac('sha256', $value, $this->appKey);
    }
}
