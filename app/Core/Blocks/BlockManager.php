<?php

declare(strict_types=1);

namespace NovaNuke\Core\Blocks;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Core\Security\HtmlSanitizer;
use PDO;
use RuntimeException;
use Twig\Markup;

final class BlockManager
{
    private const POSITIONS = ['header', 'left-sidebar', 'right-sidebar', 'before-content', 'after-content', 'footer'];

    public function __construct(
        private readonly PDO $database,
        private readonly BlockRepository $repository,
        private readonly HtmlSanitizer $sanitizer,
        private readonly BlockVisibility $visibility,
        private readonly AuthManager $auth,
        private readonly ViewRenderer $views,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->repository->all();
    }

    /** @return list<array{id: int, name: string, slug: string}> */
    public function roles(): array
    {
        return $this->repository->roles();
    }

    /** @return list<string> */
    public function positions(): array
    {
        return self::POSITIONS;
    }

    /** @param array<string, mixed> $input */
    public function save(array $input, int $actorId): int
    {
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $title = trim((string) ($input['title'] ?? ''));
        $slug = strtolower(trim((string) ($input['slug'] ?? '')));
        $position = (string) ($input['position'] ?? '');
        $mode = (string) ($input['visibility_mode'] ?? 'all');
        if ($title === '' || mb_strlen($title) > 150) {
            throw new RuntimeException('Block title is required and must not exceed 150 characters.');
        }
        if (! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $slug)) {
            throw new RuntimeException('Block slug must use lowercase letters, numbers and hyphens.');
        }
        if (! in_array($position, self::POSITIONS, true)) {
            throw new RuntimeException('Invalid block position.');
        }
        if (! in_array($mode, ['all', 'only', 'except'], true)) {
            throw new RuntimeException('Invalid page visibility mode.');
        }
        $startsAt = $this->date($input['starts_at'] ?? null, 'start');
        $endsAt = $this->date($input['ends_at'] ?? null, 'end');
        if ($startsAt !== null && $endsAt !== null && $endsAt <= $startsAt) {
            throw new RuntimeException('The end date must be later than the start date.');
        }
        $patterns = $this->lines((string) ($input['page_patterns'] ?? ''), true);
        $modules = $this->lines((string) ($input['module_slugs'] ?? ''), false);
        $roleIds = array_values(array_filter(array_map('intval', (array) ($input['role_ids'] ?? [])), static fn (int $id) => $id > 0));
        $validRoleIds = array_map(static fn (array $role): int => (int) $role['id'], $this->roles());
        if (array_diff($roleIds, $validRoleIds) !== []) {
            throw new RuntimeException('One or more selected roles are invalid.');
        }

        return $this->repository->save($id, [
            'title' => $title,
            'slug' => $slug,
            'type' => 'html',
            'position' => $position,
            'content' => $this->sanitizer->sanitize((string) ($input['content'] ?? '')),
            'configuration' => json_encode([], JSON_THROW_ON_ERROR),
            'visibility_mode' => $mode,
            'page_patterns' => json_encode($patterns, JSON_THROW_ON_ERROR),
            'module_slugs' => json_encode($modules, JSON_THROW_ON_ERROR),
            'enabled' => ($input['enabled'] ?? null) === '1' ? 1 : 0,
            'show_title' => ($input['show_title'] ?? null) === '1' ? 1 : 0,
            'sort_order' => max(-10000, min(10000, (int) ($input['sort_order'] ?? 0))),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_by' => $actorId,
        ], $roleIds);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }

    public function boot(): void
    {
        try {
            $this->database->query('SELECT 1 FROM blocks LIMIT 1');
        } catch (\PDOException) {
            return;
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            $this->views->addGlobal('blocks', array_fill_keys(self::POSITIONS, []));
            return;
        }
        $roles = $this->viewerRoles();
        $module = explode('/', trim($path, '/'))[0] ?? '';
        $regions = array_fill_keys(self::POSITIONS, []);
        foreach ($this->repository->active() as $block) {
            if (! $this->visibility->matches((string) $block['visibility_mode'], $block['page_patterns'], $path)) {
                continue;
            }
            if ($block['module_slugs'] !== [] && ! in_array($module, $block['module_slugs'], true)) {
                continue;
            }
            if ($block['role_slugs'] !== [] && array_intersect($roles, $block['role_slugs']) === []) {
                continue;
            }
            $regions[$block['position']][] = [
                'slug' => $block['slug'],
                'title' => $block['title'],
                'show_title' => (bool) $block['show_title'],
                'html' => new Markup((string) $block['content'], 'UTF-8'),
            ];
        }
        $this->views->addGlobal('blocks', $regions);
    }

    /** @return list<string> */
    private function viewerRoles(): array
    {
        $user = $this->auth->user();
        if ($user === null) {
            return ['guest'];
        }
        $statement = $this->database->prepare('SELECT r.slug FROM user_roles ur INNER JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=:id');
        $statement->execute(['id' => $user['id']]);
        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function date(mixed $value, string $label): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, new \DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d\TH:i') !== $value) {
            throw new RuntimeException("Invalid {$label} date.");
        }
        return $date->format('Y-m-d H:i:s');
    }

    /** @return list<string> */
    private function lines(string $value, bool $paths): array
    {
        $lines = preg_split('/[\r\n,]+/', $value) ?: [];
        $result = [];
        foreach ($lines as $line) {
            $line = strtolower(trim($line));
            if ($line === '') {
                continue;
            }
            $pattern = $paths
                ? '#^/(?:[a-z0-9_-]+/)*(?:[a-z0-9_-]+|\*)?$#'
                : '/^[a-z][a-z0-9-]{0,99}$/';
            if (! preg_match($pattern, $line)) {
                throw new RuntimeException($paths ? 'Invalid page pattern.' : 'Invalid module slug.');
            }
            $result[] = $line;
        }
        return array_values(array_unique($result));
    }
}
