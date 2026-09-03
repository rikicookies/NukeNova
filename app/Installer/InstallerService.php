<?php

declare(strict_types=1);

namespace NovaNuke\Installer;

use NovaNuke\Core\Database\Migrator;
use PDO;
use RuntimeException;
use Throwable;

final class InstallerService
{
    public function __construct(
        private readonly string $rootPath,
        private readonly EnvWriter $envWriter,
    ) {
    }

    /** @return list<string> */
    public function install(InstallationData $data): array
    {
        $lockPath = $this->rootPath . '/storage/installed.lock';

        if (is_file($lockPath)) {
            throw new RuntimeException('NovaNuke is already installed.');
        }

        $database = $this->connectAndCreateDatabase($data);
        $migrations = (new Migrator($database))->run($this->rootPath . '/database/migrations');

        $database->beginTransaction();
        try {
            $this->createAdministrator($database, $data);
            $this->saveInitialSettings($database, $data);
            $database->commit();
        } catch (Throwable $error) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $error;
        }

        $this->envWriter->write($this->rootPath . '/.env', [
            'APP_NAME' => $data->siteName,
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'APP_URL' => rtrim($data->siteUrl, '/'),
            'APP_TIMEZONE' => $data->timezone,
            'APP_LOCALE' => $data->locale,
            'APP_FALLBACK_LOCALE' => 'en',
            'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
            'DB_DRIVER' => 'mysql',
            'DB_HOST' => $data->databaseHost,
            'DB_PORT' => $data->databasePort,
            'DB_DATABASE' => $data->databaseName,
            'DB_USERNAME' => $data->databaseUsername,
            'DB_PASSWORD' => $data->databasePassword,
            'DB_CHARSET' => 'utf8mb4',
            'SESSION_NAME' => 'novanuke_session',
            'SESSION_SECURE' => str_starts_with(strtolower($data->siteUrl), 'https://'),
            'SESSION_SAME_SITE' => 'Lax',
        ]);

        $lock = json_encode([
            'installed_at' => gmdate('c'),
            'version' => '0.1.0-dev',
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        if (file_put_contents($lockPath, $lock . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Installation finished, but the installer lock could not be created.');
        }

        return $migrations;
    }

    private function connectAndCreateDatabase(InstallationData $data): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $serverDsn = "mysql:host={$data->databaseHost};port={$data->databasePort};charset=utf8mb4";
        $server = new PDO($serverDsn, $data->databaseUsername, $data->databasePassword, $options);
        $databaseName = $data->databaseName;

        if (! preg_match('/^[a-zA-Z0-9_]{1,64}$/', $databaseName)) {
            throw new RuntimeException('Invalid database name.');
        }

        $server->exec(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` "
            . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        return new PDO(
            $serverDsn . ";dbname={$databaseName}",
            $data->databaseUsername,
            $data->databasePassword,
            $options,
        );
    }

    private function createAdministrator(PDO $database, InstallationData $data): void
    {
        $existing = $database->prepare('SELECT COUNT(*) FROM users WHERE email = :email OR username = :username');
        $existing->execute(['email' => strtolower($data->adminEmail), 'username' => $data->adminUsername]);

        if ((int) $existing->fetchColumn() > 0) {
            throw new RuntimeException('The administrator username or email already exists.');
        }

        $statement = $database->prepare(
            'INSERT INTO users (username, email, password_hash, status, email_verified_at, created_at, updated_at) '
            . 'VALUES (:username, :email, :password_hash, :status, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        $statement->execute([
            'username' => $data->adminUsername,
            'email' => strtolower($data->adminEmail),
            'password_hash' => password_hash($data->adminPassword, PASSWORD_DEFAULT),
            'status' => 'active',
        ]);
        $userId = (int) $database->lastInsertId();

        $role = $database->prepare('SELECT id FROM roles WHERE slug = :slug');
        $role->execute(['slug' => 'super-administrator']);
        $roleId = $role->fetchColumn();

        if ($roleId === false) {
            throw new RuntimeException('The Super Administrator role was not created.');
        }

        $assignment = $database->prepare(
            'INSERT INTO user_roles (user_id, role_id, created_at) VALUES (:user_id, :role_id, UTC_TIMESTAMP())'
        );
        $assignment->execute(['user_id' => $userId, 'role_id' => (int) $roleId]);

        $profile = $database->prepare(
            'INSERT INTO user_profiles (user_id, display_name, locale, timezone, created_at, updated_at) '
            . 'VALUES (:user_id, :display_name, :locale, :timezone, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        $profile->execute([
            'user_id' => $userId,
            'display_name' => $data->adminUsername,
            'locale' => $data->locale,
            'timezone' => $data->timezone,
        ]);
    }

    private function saveInitialSettings(PDO $database, InstallationData $data): void
    {
        $statement = $database->prepare(
            'INSERT INTO settings (`key`, `value`, `type`, `group_name`, created_at, updated_at) '
            . 'VALUES (:key, :value, :type, :group_name, UTC_TIMESTAMP(), UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = UTC_TIMESTAMP()'
        );

        foreach ([
            ['site.name', $data->siteName, 'string', 'site'],
            ['site.url', rtrim($data->siteUrl, '/'), 'string', 'site'],
            ['site.locale', $data->locale, 'string', 'site'],
            ['site.timezone', $data->timezone, 'string', 'site'],
            ['users.registration_open', '0', 'boolean', 'users'],
        ] as [$key, $value, $type, $group]) {
            $statement->execute([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group_name' => $group,
            ]);
        }
    }
}
