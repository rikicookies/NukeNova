<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Database\MigrationInterruption;
use NovaNuke\Core\Database\MigrationLock;
use NovaNuke\Core\Database\Migrator;
use NovaNuke\Core\Modules\ModuleDetector;
use NovaNuke\Core\Modules\ModuleMigrator;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use PDO;
use RuntimeException;

final class MigrationRecoveryIntegrationTest extends MySqlIntegrationTestCase
{
    private ?string $migrationDirectory = null;

    protected function tearDown(): void
    {
        if ($this->migrationDirectory !== null && is_dir($this->migrationDirectory)) {
            foreach (glob($this->migrationDirectory . '/*.php') ?: [] as $file) unlink($file);
            rmdir($this->migrationDirectory);
        }
        $this->migrationDirectory = null;
        parent::tearDown();
    }

    public function testCompletedDdlIsReconciledAfterProcessInterruptionBeforeHistoryWrite(): void
    {
        $directory = $this->coreMigrationCopy();
        $name = '2099_01_01_000001_create_recovery_probe';
        $this->writeMigration($directory . '/' . $name . '.php', <<<'PHP'
<?php
use NovaNuke\Core\Database\RecoverableMigration;
use NovaNuke\Core\Database\VerifiesMigrationState;
return new class implements RecoverableMigration {
    use VerifiesMigrationState;
    private const MIGRATION_TABLES=['recovery_probe'];
    public function up(PDO $database):void{$database->exec('CREATE TABLE IF NOT EXISTS recovery_probe (id INT PRIMARY KEY) ENGINE=InnoDB');}
    public function down(PDO $database):void{$database->exec('DROP TABLE IF EXISTS recovery_probe');}
};
PHP);

        $fault = static function (string $phase, string $scope, string $migration) use ($name): void {
            if ($phase === 'after_up' && $scope === 'core' && $migration === $name) {
                throw new MigrationInterruption('Simulated process termination.');
            }
        };
        try {
            (new Migrator($this->db(), $fault))->run($directory);
            self::fail('The injected interruption should stop migration completion.');
        } catch (RuntimeException $error) {
            self::assertInstanceOf(MigrationInterruption::class, $error->getPrevious());
        }

        self::assertSame(1, $this->tableCount('recovery_probe'));
        self::assertSame(0, $this->historyCount('migrations', 'migration', $name));
        self::assertSame('running', $this->operationState('core', $name, 'up'));

        self::assertSame([$name], (new Migrator($this->db()))->recover($directory));
        self::assertSame(1, $this->historyCount('migrations', 'migration', $name));
        self::assertSame('completed', $this->operationState('core', $name, 'up'));
        self::assertSame(2, $this->operationAttempts('core', $name, 'up'));
    }

    public function testRecoveryRejectsMigrationSourceChangedAfterInterruption(): void
    {
        $directory = $this->coreMigrationCopy();
        $name = '2099_01_01_000003_checksum_recovery_probe';
        $path = $directory . '/' . $name . '.php';
        $source = <<<'PHP'
<?php
use NovaNuke\Core\Database\RecoverableMigration;
use NovaNuke\Core\Database\VerifiesMigrationState;
return new class implements RecoverableMigration {
    use VerifiesMigrationState;
    private const MIGRATION_TABLES=['checksum_recovery_probe'];
    public function up(PDO $database):void{$database->exec('CREATE TABLE IF NOT EXISTS checksum_recovery_probe (id INT PRIMARY KEY) ENGINE=InnoDB');}
    public function down(PDO $database):void{$database->exec('DROP TABLE IF EXISTS checksum_recovery_probe');}
};
PHP;
        $this->writeMigration($path, $source);
        $fault = static function (string $phase, string $scope, string $migration) use ($name): void {
            if ($phase === 'after_up' && $scope === 'core' && $migration === $name) {
                throw new MigrationInterruption('Simulated process termination.');
            }
        };
        try {
            (new Migrator($this->db(), $fault))->run($directory);
            self::fail('The injected interruption should stop migration completion.');
        } catch (RuntimeException $error) {
            self::assertInstanceOf(MigrationInterruption::class, $error->getPrevious());
        }

        $this->writeMigration($path, $source . "\n// changed after interrupted deployment\n");
        try {
            (new Migrator($this->db()))->recover($directory);
            self::fail('Recovery must reject a migration whose fingerprint changed.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('Migration changed after an interrupted attempt', (string) $error->getPrevious()?->getMessage());
        }
        self::assertSame(0, $this->historyCount('migrations', 'migration', $name));
        self::assertSame('running', $this->operationState('core', $name, 'up'));

        unlink($path);
        try {
            (new Migrator($this->db()))->recover($directory);
            self::fail('Recovery must reject a missing interrupted migration file.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('source file is missing', $error->getMessage());
        }
    }

    public function testExistingLegacyHistoryRemainsAuthoritativeWithoutBackfillOrRerun(): void
    {
        $expected = (int) $this->db()->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
        $this->db()->exec('DELETE FROM migration_operations');

        self::assertSame([], (new Migrator($this->db()))->run(dirname(__DIR__, 2) . '/database/migrations'));
        self::assertSame($expected, (int) $this->db()->query('SELECT COUNT(*) FROM migrations')->fetchColumn());
        self::assertSame(0, (int) $this->db()->query('SELECT COUNT(*) FROM migration_operations')->fetchColumn());
    }

    public function testPartiallyAppliedDdlResumesIdempotentlyAndVerifiesPostcondition(): void
    {
        $directory = $this->coreMigrationCopy();
        $name = '2099_01_01_000002_resume_partial_recovery_probe';
        $this->writeMigration($directory . '/' . $name . '.php', <<<'PHP'
<?php
use NovaNuke\Core\Database\MigrationInterruption;
use NovaNuke\Core\Database\MigrationSchema;
use NovaNuke\Core\Database\RecoverableMigration;
return new class implements RecoverableMigration {
    public function up(PDO $database):void{
        $database->exec('CREATE TABLE IF NOT EXISTS partial_recovery_probe (id INT PRIMARY KEY) ENGINE=InnoDB');
        if(!MigrationSchema::columnExists($database,'partial_recovery_probe','payload')){
            MigrationSchema::addColumn($database,'partial_recovery_probe','payload','VARCHAR(20) NULL');
            throw new RuntimeException('Simulated recoverable failure between DDL statements.');
        }
        MigrationSchema::createIndex($database,'partial_recovery_probe','partial_recovery_payload_index','payload');
    }
    public function down(PDO $database):void{$database->exec('DROP TABLE IF EXISTS partial_recovery_probe');}
    public function isApplied(PDO $database):bool{return MigrationSchema::tableExists($database,'partial_recovery_probe')&&MigrationSchema::columnExists($database,'partial_recovery_probe','payload')&&MigrationSchema::indexExists($database,'partial_recovery_probe','partial_recovery_payload_index');}
    public function isRolledBack(PDO $database):bool{return !MigrationSchema::tableExists($database,'partial_recovery_probe');}
};
PHP);

        try {
            (new Migrator($this->db()))->run($directory);
            self::fail('The migration should be interrupted after its first ALTER.');
        } catch (RuntimeException $error) {
            self::assertSame('Simulated recoverable failure between DDL statements.', $error->getPrevious()?->getMessage());
        }
        self::assertSame('dirty', $this->operationState('core', $name, 'up'));
        self::assertSame(0, $this->historyCount('migrations', 'migration', $name));

        self::assertSame([$name], (new Migrator($this->db()))->recover($directory));
        self::assertSame(1, $this->historyCount('migrations', 'migration', $name));
        self::assertSame('completed', $this->operationState('core', $name, 'up'));
    }

    public function testModuleMigrationUsesTheSameCrashRecoveryProtocol(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = (new ModuleDetector($root . '/modules'))->detect()['welcome'];
        $name = '2026_09_01_000001_create_welcome_messages_table';
        $fault = static function (string $phase, string $scope, string $migration) use ($name): void {
            if ($phase === 'after_up' && $scope === 'module:welcome' && $migration === $name) {
                throw new MigrationInterruption('Simulated module process termination.');
            }
        };

        try {
            (new ModuleMigrator($this->db(), $fault))->run($manifest);
            self::fail('The injected module interruption should stop history completion.');
        } catch (RuntimeException $error) {
            self::assertInstanceOf(MigrationInterruption::class, $error->getPrevious());
        }
        self::assertSame(1, $this->tableCount('welcome_messages'));
        self::assertSame(0, $this->moduleHistoryCount('welcome', $name));
        self::assertSame('running', $this->operationState('module:welcome', $name, 'up'));

        self::assertSame([$name], (new ModuleMigrator($this->db()))->recover($manifest));
        self::assertSame(1, $this->moduleHistoryCount('welcome', $name));
        self::assertSame('completed', $this->operationState('module:welcome', $name, 'up'));
    }

    public function testInterruptedModuleUninstallReconcilesAlreadyRemovedSchema(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = (new ModuleDetector($root . '/modules'))->detect()['welcome'];
        $name = '2026_09_01_000001_create_welcome_messages_table';
        (new ModuleMigrator($this->db()))->run($manifest);
        $fault = static function (string $phase, string $scope, string $migration) use ($name): void {
            if ($phase === 'after_down' && $scope === 'module:welcome' && $migration === $name) {
                throw new MigrationInterruption('Simulated termination during module uninstall.');
            }
        };

        try {
            (new ModuleMigrator($this->db(), $fault))->rollbackAll($manifest);
            self::fail('The injected uninstall interruption should stop ledger cleanup.');
        } catch (MigrationInterruption) {
        }
        self::assertSame(0, $this->tableCount('welcome_messages'));
        self::assertSame(1, $this->moduleHistoryCount('welcome', $name));
        self::assertSame('running', $this->operationState('module:welcome', $name, 'down'));

        self::assertSame([$name], (new ModuleMigrator($this->db()))->recover($manifest));
        self::assertSame(0, $this->moduleHistoryCount('welcome', $name));
        self::assertSame('completed', $this->operationState('module:welcome', $name, 'down'));
    }

    public function testConcurrentRunnerCannotEnterWhileSchemaLockIsHeld(): void
    {
        $lock = new MigrationLock($this->db(), 0);
        $lock->acquire();
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('already running');
            (new Migrator($this->secondConnection()))->run(dirname(__DIR__, 2) . '/database/migrations');
        } finally {
            $lock->release();
        }
    }

    private function coreMigrationCopy(): string
    {
        if ($this->migrationDirectory !== null) return $this->migrationDirectory;
        $this->migrationDirectory = sys_get_temp_dir() . '/novanuke-recovery-' . bin2hex(random_bytes(8));
        mkdir($this->migrationDirectory, 0750, true);
        foreach (glob(dirname(__DIR__, 2) . '/database/migrations/*.php') ?: [] as $file) {
            copy($file, $this->migrationDirectory . '/' . basename($file));
        }
        return $this->migrationDirectory;
    }

    private function writeMigration(string $path, string $source): void
    {
        if (file_put_contents($path, $source) === false) self::fail('Unable to create fault-injection migration.');
    }

    private function operationState(string $scope, string $migration, string $direction): string
    {
        $statement = $this->db()->prepare('SELECT state FROM migration_operations WHERE scope=:scope AND migration=:migration AND direction=:direction');
        $statement->execute(compact('scope', 'migration', 'direction'));
        return (string) $statement->fetchColumn();
    }

    private function operationAttempts(string $scope, string $migration, string $direction): int
    {
        $statement = $this->db()->prepare('SELECT attempts FROM migration_operations WHERE scope=:scope AND migration=:migration AND direction=:direction');
        $statement->execute(compact('scope', 'migration', 'direction'));
        return (int) $statement->fetchColumn();
    }

    private function tableCount(string $table): int
    {
        $statement = $this->db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table');
        $statement->execute(compact('table'));
        return (int) $statement->fetchColumn();
    }

    private function historyCount(string $table, string $column, string $name): int
    {
        if ($table !== 'migrations' || $column !== 'migration') self::fail('Unsafe history lookup.');
        $statement = $this->db()->prepare('SELECT COUNT(*) FROM migrations WHERE migration=:name');
        $statement->execute(compact('name'));
        return (int) $statement->fetchColumn();
    }

    private function moduleHistoryCount(string $slug, string $name): int
    {
        $statement = $this->db()->prepare('SELECT COUNT(*) FROM module_migrations WHERE module_slug=:slug AND migration=:name');
        $statement->execute(compact('slug', 'name'));
        return (int) $statement->fetchColumn();
    }

    private function secondConnection(): PDO
    {
        $host = (string) env('NOVANUKE_TEST_DB_HOST', '127.0.0.1');
        $port = (int) env('NOVANUKE_TEST_DB_PORT', 3306);
        $schema = (string) $this->db()->query('SELECT DATABASE()')->fetchColumn();
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$schema};charset=utf8mb4",
            (string) env('NOVANUKE_TEST_DB_USERNAME', 'root'),
            (string) env('NOVANUKE_TEST_DB_PASSWORD', ''),
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false],
        );
    }
}
