<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Auth\ProfileRepository;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;

final class ProfileRepositoryIntegrationTest extends MySqlIntegrationTestCase
{
    public function testMissingProfileUsesSafeDefaultsAndIsRecreatedOnSave(): void
    {
        $statement = $this->db()->prepare(
            'INSERT INTO users (username,email,password_hash,status,created_at,updated_at) '
            . 'VALUES (:username,:email,:password,:status,UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $statement->execute([
            'username' => 'profile-recovery',
            'email' => 'profile-recovery@example.test',
            'password' => password_hash('Correct-Horse-92!', PASSWORD_DEFAULT),
            'status' => 'active',
        ]);
        $userId = (int) $this->db()->lastInsertId();
        $profiles = new ProfileRepository($this->db());

        $profile = $profiles->byUserId($userId);
        self::assertSame('profile-recovery', $profile['display_name']);
        self::assertSame('en', $profile['locale']);
        self::assertSame('UTC', $profile['timezone']);
        self::assertSame('public', $profile['profile_visibility']);

        $profiles->update($userId, [
            'display_name' => 'Recovered Member',
            'bio' => '',
            'locale' => 'es',
            'timezone' => 'America/Los_Angeles',
            'preferences' => ['profile_visibility' => 'members'],
        ]);

        self::assertSame(1, (int) $this->db()->query(
            'SELECT COUNT(*) FROM user_profiles WHERE user_id=' . $userId
        )->fetchColumn());
        $recovered = $profiles->byUsername('profile-recovery');
        self::assertSame('Recovered Member', $recovered['display_name']);
        self::assertSame('members', $recovered['profile_visibility']);
    }
}
