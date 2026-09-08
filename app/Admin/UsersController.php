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
use NovaNuke\Auth\RegistrationValidator;
use NovaNuke\Auth\PasswordPolicy;
use PDO;
use PDOException;

final class UsersController
{
    public function __construct(
        private readonly PDO $database,
        private readonly AuthManager $auth,
        private readonly AuthorizationService $authorization,
        private readonly ActivityLogger $activity,
        private readonly CsrfTokenManager $csrf,
        private readonly ViewRenderer $views,
        private readonly RegistrationValidator $validator,
        private readonly PasswordPolicy $passwordPolicy,
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

        return $user === null ? Response::html('User not found.', 404) : $this->editView($user, false, $request->query('password_reset') === '1');
    }

    public function create(): Response
    {
        if ($guard = $this->creationGuard()) return $guard;

        return $this->createView();
    }

    public function store(Request $request): Response
    {
        if ($guard = $this->creationGuard()) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) {
            return Response::html('Invalid or expired CSRF token.', 419);
        }

        $input = [
            'username' => trim((string) $request->input('username', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'password' => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
        ];
        $errors = $this->validator->validate($input);
        $roleId = filter_var($request->input('role_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $validRoles = $roleId === false ? [] : $this->validRoleIds([(int) $roleId]);
        if ($validRoles === []) $errors['role_id'] = 'Select a valid role.';
        $old = ['username' => $input['username'], 'email' => $input['email'], 'role_id' => $roleId === false ? '' : (int) $roleId];
        if ($errors !== []) return $this->createView($old, $errors);

        $actor = $this->auth->user();
        $this->database->beginTransaction();
        try {
            $user = $this->database->prepare(
                "INSERT INTO users (username,email,password_hash,must_change_password,auth_version,status,email_verified_at,created_at,updated_at) VALUES (:username,:email,:password_hash,:must_change_password,1,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())"
            );
            $user->execute(['username' => $input['username'], 'email' => $input['email'], 'password_hash' => password_hash((string) $input['password'], PASSWORD_DEFAULT), 'must_change_password' => $request->input('must_change_password') === '1' ? 1 : 0]);
            $userId = (int) $this->database->lastInsertId();
            $profile = $this->database->prepare('INSERT INTO user_profiles (user_id,display_name,locale,timezone,preferences,created_at,updated_at) VALUES (:user_id,:display_name,:locale,:timezone,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $profile->execute(['user_id' => $userId, 'display_name' => $input['username'], 'locale' => 'en', 'timezone' => 'UTC']);
            $assignment = $this->database->prepare('INSERT INTO user_roles (user_id,role_id,created_at) VALUES (:user_id,:role_id,UTC_TIMESTAMP())');
            $assignment->execute(['user_id' => $userId, 'role_id' => $validRoles[0]]);
            $this->database->commit();
        } catch (PDOException $error) {
            if ($this->database->inTransaction()) $this->database->rollBack();
            if ($error->getCode() === '23000') {
                $errors['account'] = 'That username or email address is already in use.';
                return $this->createView($old, $errors);
            }
            throw $error;
        } catch (\Throwable $error) {
            if ($this->database->inTransaction()) $this->database->rollBack();
            throw $error;
        }

        $this->activity->log((int) $actor['id'], 'user.created', 'user', $userId, ['role_id' => (string) $validRoles[0], 'must_change_password' => $request->input('must_change_password') === '1'], $request->ip());
        return Response::redirect('/admin/users/'.$userId, 303);
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

    public function resetPassword(Request $request): Response
    {
        if ($guard = $this->creationGuard()) return $guard;
        if (! $this->csrf->validate($request->input('_token'))) return Response::html('Invalid or expired CSRF token.', 419);

        $actor = $this->auth->user();
        $target = $this->user((int) $request->attribute('id'));
        if ($target === null) return Response::html('User not found.', 404);
        if ((int) $actor['id'] === (int) $target['id']) return Response::html('Change your own password from Account settings.', 422);

        $error = $this->passwordPolicy->validate($request->input('password'), $request->input('password_confirmation'));
        if ($error !== null) return $this->editView($target, false, false, $error, 422);

        $this->database->beginTransaction();
        try {
            $update = $this->database->prepare('UPDATE users SET password_hash=:hash,must_change_password=1,auth_version=auth_version+1,updated_at=UTC_TIMESTAMP() WHERE id=:id AND deleted_at IS NULL');
            $update->execute(['hash' => password_hash((string) $request->input('password'), PASSWORD_DEFAULT), 'id' => $target['id']]);
            if ($update->rowCount() !== 1) throw new \RuntimeException('User password could not be updated.');
            $this->database->prepare('DELETE FROM password_reset_tokens WHERE user_id=:id')->execute(['id' => $target['id']]);
            $this->database->prepare('DELETE FROM email_change_tokens WHERE user_id=:id')->execute(['id' => $target['id']]);
            $this->database->commit();
        } catch (\Throwable $error) {
            if ($this->database->inTransaction()) $this->database->rollBack();
            throw $error;
        }

        $this->activity->log((int) $actor['id'], 'user.password.reset_by_admin', 'user', $target['id'], [], $request->ip());
        return Response::redirect('/admin/users/'.$target['id'].'?password_reset=1', 303);
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

    private function creationGuard(): ?Response
    {
        if ($guard = $this->guard('users.manage')) return $guard;
        if ($guard = $this->guard('users.assign_roles')) return $guard;
        $actor = $this->auth->user();
        return $this->auth->isSuperAdministrator((int) $actor['id']) ? null : Response::html('Forbidden', 403);
    }

    /** @param array<string,mixed> $old @param array<string,string> $errors */
    private function createView(array $old = [], array $errors = []): Response
    {
        return Response::html($this->views->render('admin/users/create.twig', [
            'roles' => $this->database->query('SELECT id,name,slug FROM roles ORDER BY id')->fetchAll(),
            'csrf_token' => $this->csrf->token(), 'old' => $old, 'errors' => $errors,
        ]));
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
    private function editView(array $user, bool $saved = false, bool $passwordReset = false, ?string $passwordError = null, int $status = 200): Response
    {
        return Response::html($this->views->render('admin/users/edit.twig', [
            'edited_user' => $user,
            'roles' => $this->database->query('SELECT id, name, slug, description FROM roles ORDER BY id')->fetchAll(),
            'assigned_roles' => $this->assignedRoleIds((int) $user['id']),
            'csrf_token' => $this->csrf->token(),
            'saved' => $saved,
            'password_reset' => $passwordReset,
            'password_error' => $passwordError,
            'current_user_id' => (int) $this->auth->user()['id'],
        ]), $status);
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
