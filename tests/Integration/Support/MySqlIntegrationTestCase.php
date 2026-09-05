<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration\Support;

use NovaNuke\Core\Database\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class MySqlIntegrationTestCase extends TestCase
{
    protected ?PDO $database = null;
    private ?PDO $server = null;
    private ?string $databaseName = null;

    protected function setUp(): void
    {
        if ((string) env('NOVANUKE_RUN_INTEGRATION', '') !== '1') {
            self::markTestSkipped('Run composer test:integration to create an isolated temporary database.');
        }

        $host = (string) env('NOVANUKE_TEST_DB_HOST', '127.0.0.1');
        $port = filter_var(env('NOVANUKE_TEST_DB_PORT', '3306'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        if ($port === false) self::fail('NOVANUKE_TEST_DB_PORT is invalid.');

        $this->databaseName = 'novanuke_test_' . bin2hex(random_bytes(8));
        if (! preg_match('/^novanuke_test_[a-f0-9]{16}$/', $this->databaseName)) {
            self::fail('Unsafe integration database name.');
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->server = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            (string) env('NOVANUKE_TEST_DB_USERNAME', 'root'),
            (string) env('NOVANUKE_TEST_DB_PASSWORD', ''),
            $options,
        );
        $this->server->exec("CREATE DATABASE `{$this->databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        try {
            $this->database = new PDO(
                "mysql:host={$host};port={$port};dbname={$this->databaseName};charset=utf8mb4",
                (string) env('NOVANUKE_TEST_DB_USERNAME', 'root'),
                (string) env('NOVANUKE_TEST_DB_PASSWORD', ''),
                $options,
            );
            (new Migrator($this->database))->run(dirname(__DIR__, 3) . '/database/migrations');
        } catch (Throwable $error) {
            $this->database = null;
            $this->server->exec("DROP DATABASE `{$this->databaseName}`");
            $this->server = null;
            $this->databaseName = null;
            throw $error;
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        $_SESSION = [];
        $this->database = null;

        if ($this->server !== null && $this->databaseName !== null
            && preg_match('/^novanuke_test_[a-f0-9]{16}$/', $this->databaseName)) {
            try {
                $this->server->exec("DROP DATABASE `{$this->databaseName}`");
            } catch (Throwable $error) {
                fwrite(STDERR, "Unable to remove temporary integration database {$this->databaseName}: {$error->getMessage()}\n");
            }
        }
        $this->server = null;
        $this->databaseName = null;
    }

    protected function db(): PDO
    {
        return $this->database ?? throw new \LogicException('Integration database is not available.');
    }
}
