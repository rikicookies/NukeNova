<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use Modules\Notifications\src\NotificationPublisher;
use Modules\Notifications\src\NotificationRepository;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use RuntimeException;

final class NotificationsIntegrationTest extends MySqlIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $migration = require dirname(__DIR__, 2) . '/modules/Notifications/database/migrations/2026_09_07_000001_create_notifications_table.php';
        $migration->up($this->db());
    }

    public function testPublishingDeduplicationAndOwnershipProtectedReading(): void
    {
        $first = $this->user('notify-first');
        $second = $this->user('notify-second');
        $repository = new NotificationRepository($this->db());
        $publisher = new NotificationPublisher($repository);

        $publisher->toUser($first, 'test.notice', 'Test notice', 'A safe notification.', '/account/profile', 'test:1');
        $publisher->toUser($first, 'test.notice', 'Test notice', 'A safe notification.', '/account/profile', 'test:1');
        self::assertSame(1, $repository->unreadCount($first));
        $notification = $repository->latest($first)[0];

        $this->expectException(RuntimeException::class);
        try {
            $repository->markRead((int) $notification['id'], $second);
        } finally {
            self::assertSame(1, $repository->unreadCount($first));
        }
    }

    public function testMarkAllAndRetentionDryRun(): void
    {
        $user = $this->user('notify-retention');
        $repository = new NotificationRepository($this->db());
        $publisher = new NotificationPublisher($repository);
        $publisher->toUser($user, 'test.notice', 'One', 'First notification.');
        $publisher->toUser($user, 'test.notice', 'Two', 'Second notification.');
        self::assertSame(2, $repository->markAllRead($user));
        $this->db()->exec("UPDATE notifications SET read_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 91 DAY)");
        self::assertSame(2, $repository->prune(true));
        self::assertSame(2, $repository->prune(false));
    }

    public function testPermissionPublishingIncludesSuperAdministrator(): void
    {
        $user = $this->user('notify-super');
        $this->db()->prepare(
            "INSERT INTO user_roles (user_id,role_id,created_at) SELECT :user,id,UTC_TIMESTAMP() FROM roles WHERE slug='super-administrator'"
        )->execute(['user' => $user]);
        $repository = new NotificationRepository($this->db());
        (new NotificationPublisher($repository))->toPermission(
            'comments.moderate', 'comment.pending', 'Pending comment', 'A comment requires review.', '/admin/comments', 'comment:88'
        );

        self::assertSame(1, $repository->unreadCount($user));
    }

    private function user(string $username): int
    {
        $statement = $this->db()->prepare(
            'INSERT INTO users (username,email,password_hash,status,email_verified_at,created_at,updated_at) '
            . 'VALUES (:username,:email,:password,\'active\',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $statement->execute([
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => password_hash('Integration-Password-92!', PASSWORD_DEFAULT),
        ]);
        return (int) $this->db()->lastInsertId();
    }
}
