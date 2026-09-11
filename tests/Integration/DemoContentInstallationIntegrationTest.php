<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use Modules\DemoContent\src\DemoContentInstaller;
use NovaNuke\Core\Access\EntitlementService;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use RuntimeException;

final class DemoContentInstallationIntegrationTest extends MySqlIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $migration = require dirname(__DIR__, 2) . '/modules/DemoContent/database/migrations/2026_09_09_000001_create_demo_content_tables.php';
        $migration->up($this->db());
    }

    public function testCoreOnlyInstallationCreatesAccountsAndPreservesExistingUsers(): void
    {
        $actor = $this->createExistingAdministrator();
        $installer = new DemoContentInstaller(
            $this->db(),
            new Container(),
            new EntitlementService($this->db()),
            new EventDispatcher(),
        );

        $result = $installer->install($actor);

        self::assertSame(12, $result['counts']['Users']);
        self::assertSame(3, $result['counts']['VIP entitlements']);
        self::assertSame([], $result['modules']);
        self::assertSame(13, (int) $this->db()->query('SELECT COUNT(*) FROM users')->fetchColumn());
        self::assertSame(12, (int) $this->db()->query("SELECT COUNT(*) FROM users WHERE email LIKE '%@demo.novanuke.test'")->fetchColumn());
        self::assertSame(3, (int) $this->db()->query("SELECT COUNT(*) FROM user_entitlements WHERE entitlement='vip'")->fetchColumn());
        self::assertSame('installed', $installer->status()['status']);
        self::assertSame('existing-admin', (string) $this->db()->query("SELECT username FROM users WHERE id={$actor}")->fetchColumn());
    }

    public function testDatasetCannotBeInstalledTwice(): void
    {
        $actor = $this->createExistingAdministrator();
        $installer = new DemoContentInstaller($this->db(), new Container(), new EntitlementService($this->db()), new EventDispatcher());
        $installer->install($actor);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already installed');
        $installer->install($actor);
    }

    private function createExistingAdministrator(): int
    {
        $statement = $this->db()->prepare(
            "INSERT INTO users(username,email,password_hash,must_change_password,auth_version,status,email_verified_at,created_at,updated_at) VALUES('existing-admin','existing@example.test',:password,0,1,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $statement->execute(['password' => password_hash('Existing-Test-Password-2026!', PASSWORD_DEFAULT)]);
        return (int) $this->db()->lastInsertId();
    }
}
