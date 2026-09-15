<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Database\MigrationInterruption;
use NovaNuke\Core\Database\MigrationLock;
use NovaNuke\Core\Database\Migrator;
use NovaNuke\Core\Modules\ModuleDetector;
use NovaNuke\Core\Modules\ModuleMigrator;
use NovaNuke\Core\Modules\ModuleManifest;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use PDO;
use RuntimeException;

final class MigrationRecoveryIntegrationTest extends MySqlIntegrationTestCase
{
    private ?string $migrationDirectory = null;

    protected function tearDown(): void
    {
        if ($this->migrationDirectory !== null && is_dir($this->migrationDirectory)) {
            $this->removeTree($this->migrationDirectory);
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
        $later='2099_01_01_000003_later_migration_probe';
        $this->writeMigration($directory.'/'.$later.'.php', <<<'PHP'
<?php
use NovaNuke\Core\Database\RecoverableMigration;
use NovaNuke\Core\Database\VerifiesMigrationState;
return new class implements RecoverableMigration {
    use VerifiesMigrationState;
    private const MIGRATION_TABLES=['later_migration_probe'];
    public function up(PDO $database):void{$database->exec('CREATE TABLE IF NOT EXISTS later_migration_probe (id INT PRIMARY KEY) ENGINE=InnoDB');}
    public function down(PDO $database):void{$database->exec('DROP TABLE IF EXISTS later_migration_probe');}
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

        try {
            (new Migrator($this->db()))->run($directory);
            self::fail('Ordinary migrate must require explicit recovery for a dirty operation.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('Run php bin/cms migrate:recover first', $error->getMessage());
        }
        self::assertSame(0,$this->tableCount('later_migration_probe'));

        self::assertSame([$name], (new Migrator($this->db()))->recover($directory));
        self::assertSame(1, $this->historyCount('migrations', 'migration', $name));
        self::assertSame('completed', $this->operationState('core', $name, 'up'));
        self::assertSame(0,$this->tableCount('later_migration_probe'),'Recovery must not run unrelated pending migrations.');
    }

    public function testFailureBeforeFirstDdlIsDurableAndRecoverable(): void
    {
        $directory=$this->coreMigrationCopy();
        $name='2099_01_01_000004_before_ddl_probe';
        $this->writeMigration($directory.'/'.$name.'.php', <<<'PHP'
<?php
use NovaNuke\Core\Database\RecoverableMigration;
use NovaNuke\Core\Database\VerifiesMigrationState;
return new class implements RecoverableMigration {
    use VerifiesMigrationState;
    private const MIGRATION_TABLES=['before_ddl_probe'];
    public function up(PDO $database):void{$database->exec('CREATE TABLE IF NOT EXISTS before_ddl_probe (id INT PRIMARY KEY) ENGINE=InnoDB');}
    public function down(PDO $database):void{$database->exec('DROP TABLE IF EXISTS before_ddl_probe');}
};
PHP);
        $fault=static function(string$phase,string$scope,string$migration)use($name):void{
            if($phase==='before_up'&&$scope==='core'&&$migration===$name)throw new RuntimeException('Injected failure before first DDL.');
        };
        try{(new Migrator($this->db(),$fault))->run($directory);self::fail('The pre-DDL fault must stop the migration.');}
        catch(RuntimeException$error){self::assertSame('Injected failure before first DDL.',$error->getPrevious()?->getMessage());}
        self::assertSame(0,$this->tableCount('before_ddl_probe'));
        self::assertSame('dirty',$this->operationState('core',$name,'up'));
        self::assertSame([$name],(new Migrator($this->db()))->recover($directory));
        self::assertSame(1,$this->tableCount('before_ddl_probe'));
        self::assertSame('completed',$this->operationState('core',$name,'up'));
    }

    public function testModulePartialDdlRequiresExplicitRecoveryAndResumesMissingStep(): void
    {
        $root=$this->moduleMigrationRoot();
        $name='2099_01_01_000001_module_partial_probe';
        $this->writeMigration($root.'/database/migrations/'.$name.'.php', <<<'PHP'
<?php
use NovaNuke\Core\Database\MigrationSchema;
use NovaNuke\Core\Database\RecoverableMigration;
return new class implements RecoverableMigration {
    public function up(PDO $database):void{
        $database->exec('CREATE TABLE IF NOT EXISTS module_partial_probe (id INT PRIMARY KEY) ENGINE=InnoDB');
        if(!MigrationSchema::columnExists($database,'module_partial_probe','payload')){
            MigrationSchema::addColumn($database,'module_partial_probe','payload','VARCHAR(20) NULL');
            throw new RuntimeException('Injected module failure between DDL statements.');
        }
        MigrationSchema::createIndex($database,'module_partial_probe','module_partial_payload_index','payload');
    }
    public function down(PDO $database):void{$database->exec('DROP TABLE IF EXISTS module_partial_probe');}
    public function isApplied(PDO $database):bool{return MigrationSchema::tableExists($database,'module_partial_probe')&&MigrationSchema::columnExists($database,'module_partial_probe','payload')&&MigrationSchema::indexExists($database,'module_partial_probe','module_partial_payload_index');}
    public function isRolledBack(PDO $database):bool{return !MigrationSchema::tableExists($database,'module_partial_probe');}
};
PHP);
        $manifest=ModuleManifest::fromArray([
            'name'=>'Recovery Probe','slug'=>'recovery-probe','version'=>'1.0.0','provider'=>'Modules\\RecoveryProbe\\RecoveryProbeModule',
            'cms_min_version'=>'0.4.0-rc.3','php_min_version'=>'8.3.0','dependencies'=>[],'permissions'=>[],'events'=>[],
        ],$root);
        try{(new ModuleMigrator($this->db()))->run($manifest);self::fail('The module migration must fail between DDL statements.');}
        catch(RuntimeException$error){self::assertSame('Injected module failure between DDL statements.',$error->getPrevious()?->getMessage());}
        self::assertSame('dirty',$this->operationState('module:recovery-probe',$name,'up'));
        try{(new ModuleMigrator($this->db()))->run($manifest);self::fail('Ordinary module update must require explicit recovery.');}
        catch(RuntimeException$error){self::assertStringContainsString('migrate:recover',$error->getMessage());}
        self::assertSame([$name],(new ModuleMigrator($this->db()))->recover($manifest));
        self::assertSame('completed',$this->operationState('module:recovery-probe',$name,'up'));
        self::assertSame(1,$this->moduleHistoryCount('recovery-probe',$name));
        self::assertSame(2,$this->operationAttempts('module:recovery-probe',$name,'up'));
    }

    public function testNonRecoverableDirtyMigrationStopsWithoutBlindRetry(): void
    {
        $directory=$this->coreMigrationCopy();$name='2099_01_01_000005_nonrecoverable_probe';
        $this->writeMigration($directory.'/'.$name.'.php', <<<'PHP'
<?php
use NovaNuke\Core\Database\Migration;
return new class implements Migration {
    public function up(PDO $database):void{throw new RuntimeException('Legacy migration failure.');}
    public function down(PDO $database):void{}
};
PHP);
        try{(new Migrator($this->db()))->run($directory);self::fail('The legacy migration must fail.');}catch(RuntimeException){}
        self::assertSame('dirty',$this->operationState('core',$name,'up'));
        try{(new Migrator($this->db()))->recover($directory);self::fail('Recovery must reject a non-recoverable migration.');}
        catch(RuntimeException$error){self::assertStringContainsString('Interrupted legacy migration requires a recoverable implementation',(string)$error->getPrevious()?->getMessage());}
        self::assertSame(1,$this->operationAttempts('core',$name,'up'));
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

    private function moduleMigrationRoot(): string
    {
        $base=$this->coreMigrationCopy().'/RecoveryProbe';
        if(!is_dir($base.'/database/migrations'))mkdir($base.'/database/migrations',0750,true);
        return $base;
    }

    private function removeTree(string $root): void
    {
        $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);
        foreach($iterator as$item){$item->isDir()?rmdir($item->getPathname()):unlink($item->getPathname());}
        rmdir($root);
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
