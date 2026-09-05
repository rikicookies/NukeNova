<?php

declare(strict_types=1);

namespace NovaNuke\Admin;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Logging\ActivityLogger;
use NovaNuke\Core\Security\AuthorizationService;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Core\Security\UserRoleSafety;
use PDO;

final class UsersController
{
    public function __construct(
        private readonly PDO $database,
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
    ) {
    }

    public function index(): Response
    {
        $guard = $this->guard('users.view');
        if ($guard !== null) {
            return $guard;
        }
        $users = $this->database->query(
            "SELECT u.id, u.username, u.email, u.status, u.last_login_at, u.created_at, "
            . "COALESCE(GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR ', '), '') AS roles "
            . 'FROM users u LEFT JOIN user_roles ur ON ur.user_id = u.id '
            . 'LEFT JOIN roles r ON r.id = ur.role_id WHERE u.deleted_at IS NULL '
            . 'GROUP BY u.id, u.username, u.email, u.status, u.last_login_at, u.created_at '
            . 'ORDER BY u.created_at DESC LIMIT 200'
        )->fetchAll();

        return Response::html($this->views->render('admin/users/index.twig', ['users' => $users]));
    }

    public function edit(Request $request): Response
    {
        $guard = $this->guard('users.view');
        if ($guard !== null) {
            return $guard;
        }
        $user = $this->user((int) $request->attribute('id'));

        return $user === null ? Response::html('User not found.', 404) : $this->editView($user);
    }

    public function update(Request $request): Response
    {
        $guard = $this->guard('users.manage');
        if ($guard !== null) {
            return $guard;
        }
        $guard = $this->guard('users.assign_roles');
        if ($guard !== null) {
            return $guard;
        }
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $actor = $this->auth->user();
        $target = $this->user((int) $request->attribute('id'));
        if ($target === null) {
            return Response::html('User not found.', 404);
        }
        $status = (string) $request->input('status');
        if (! in_array($status, ['active', 'suspended'], true)) {
            return Response::html('Invalid account status.', 422);
        }
        $selected = $request->input('roles', []);
        $selected = is_array($selected) ? array_values(array_unique(array_map('intval', $selected))) : [];
        $validRoles = $this->validRoleIds($selected);
        $superRoleId = $this->roleId('super-administrator');
        $targetIsSuper = $superRoleId !== null && in_array($superRoleId, $this->assignedRoleIds((int) $target['id']), true);
        $actorIsSuper = $this->auth->isSuperAdministrator((int) $actor['id']);

        $violation = (new UserRoleSafety())->violation(
            (int) $actor['id'],
            (int) $target['id'],
            $actorIsSuper,
            $targetIsSuper,
            $status === 'active',
            $superRoleId !== null && in_array($superRoleId, $validRoles, true),
            $this->activeSuperAdministratorCount(),
        );
        if ($violation !== null) {
            return Response::html($violation, 422);
        }

        $this->database->beginTransaction();
        try {
            $update = $this->database->prepare(
                'UPDATE users SET status = :status, suspended_at = :suspended_at, '
                . 'auth_version = auth_version + :auth_increment, updated_at = UTC_TIMESTAMP() '
                . 'WHERE id = :id'
            );
            $update->execute([
                'status' => $status,
                'suspended_at' => $status === 'suspended' ? gmdate('Y-m-d H:i:s') : null,
                'auth_increment' => $target['status'] === $status ? 0 : 1,
                'id' => $target['id'],
            ]);
            $delete = $this->database->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
            $delete->execute(['user_id' => $target['id']]);
            $insert = $this->database->prepare(
                'INSERT INTO user_roles (user_id, role_id, created_at) VALUES (:user_id, :role_id, UTC_TIMESTAMP())'
            );
            foreach ($validRoles as $roleId) {
                $insert->execute(['user_id' => $target['id'], 'role_id' => $roleId]);
            }
            $this->database->commit();
        } catch (\Throwable $error) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $error;
        }

        $this->activity->log((int) $actor['id'], 'user.authorization.updated', 'user', $target['id'], [
            'status' => $status,
            'role_ids' => implode(',', $validRoles),
        ], $request->ip());
        return $this->editView($this->user((int) $target['id']), true);
    }

    private function guard(string $permission): ?Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        return $this->authorization->allows((int) $user['id'], $permission)
            ? null
            : Response::html('Forbidden', 403);
    }

    /** @return array<string, mixed>|null */
    private function user(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, username, email, status, email_verified_at, last_login_at, created_at '
            . 'FROM users WHERE id = :id AND deleted_at IS NULL'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /** @param array<string, mixed> $user */
    private function editView(array $user, bool $saved = false): Response
    {
        return Response::html($this->views->render('admin/users/edit.twig', [
            'edited_user' => $user,
            'roles' => $this->database->query('SELECT id, name, slug, description FROM roles ORDER BY id')->fetchAll(),
            'assigned_roles' => $this->assignedRoleIds((int) $user['id']),
            'csrf_token' => $this->csrf->token(),
            'saved' => $saved,
            'current_user_id' => (int) $this->auth->user()['id'],
        ]));
    }

    /** @param list<int> $ids
     *  @return list<int>
     */
    private function validRoleIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->database->prepare("SELECT id FROM roles WHERE id IN ({$placeholders})");
        $statement->execute($ids);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return list<int> */
    private function assignedRoleIds(int $userId): array
    {
        $statement = $this->database->prepare('SELECT role_id FROM user_roles WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function roleId(string $slug): ?int
    {
        $statement = $this->database->prepare('SELECT id FROM roles WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    private function activeSuperAdministratorCount(): int
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(DISTINCT u.id) FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id '
            . 'INNER JOIN roles r ON r.id = ur.role_id '
            . 'WHERE r.slug = :slug AND u.status = :status AND u.deleted_at IS NULL'
        );
        $statement->execute(['slug' => 'super-administrator', 'status' => 'active']);

        return (int) $statement->fetchColumn();
    }
}
