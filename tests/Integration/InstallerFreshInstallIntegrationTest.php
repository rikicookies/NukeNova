<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Version;
use NovaNuke\Installer\EnvWriter;
use NovaNuke\Installer\InstallationData;
use NovaNuke\Installer\InstallerService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class InstallerFreshInstallIntegrationTest extends TestCase
{
    private ?PDO $server=null;
    private ?string $databaseName=null;
    private ?string $root=null;

    protected function setUp(): void
    {
        if((string)env('NOVANUKE_RUN_INTEGRATION','')!=='1'){
            self::markTestSkipped('Run composer test:integration to create an isolated temporary database.');
        }

        $host=(string)env('NOVANUKE_TEST_DB_HOST','127.0.0.1');
        $port=filter_var(env('NOVANUKE_TEST_DB_PORT','3306'),FILTER_VALIDATE_INT,[
            'options'=>['min_range'=>1,'max_range'=>65535],
        ]);
        if($port===false) self::fail('NOVANUKE_TEST_DB_PORT is invalid.');

        $this->databaseName='novanuke_installer_'.bin2hex(random_bytes(8));
        $this->root=sys_get_temp_dir().'/novanuke-installer-'.bin2hex(random_bytes(8));

        mkdir($this->root.'/database/migrations',0770,true);
        $source=dirname(__DIR__,2).'/database/migrations';
        foreach(glob($source.'/*.php')?:[] as $migration){
            copy($migration,$this->root.'/database/migrations/'.basename($migration));
        }

        $options=[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ];
        $this->server=new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            (string)env('NOVANUKE_TEST_DB_USERNAME','root'),
            (string)env('NOVANUKE_TEST_DB_PASSWORD',''),
            $options,
        );
    }

    protected function tearDown(): void
    {
        if($this->server!==null&&$this->databaseName!==null
            &&preg_match('/^novanuke_installer_[a-f0-9]{16}$/',$this->databaseName)){
            try{
                $this->server->exec("DROP DATABASE IF EXISTS `{$this->databaseName}`");
            }catch(Throwable $error){
                fwrite(STDERR,"Unable to remove installer test database {$this->databaseName}: {$error->getMessage()}\n");
            }
        }
        $this->server=null;
        $this->databaseName=null;

        if($this->root!==null&&is_dir($this->root)) $this->removeTree($this->root);
        $this->root=null;
    }

    public function testFreshInstallCreatesVersionedSiteWithoutManualIntervention(): void
    {
        $data=$this->data();
        $migrations=(new InstallerService($this->root(),new EnvWriter()))->install($data);

        self::assertNotEmpty($migrations);
        self::assertFileExists($this->root().'/.env');
        self::assertFileExists($this->root().'/storage/installed.lock');

        $env=(string)file_get_contents($this->root().'/.env');
        self::assertStringContainsString('APP_ENV="production"',$env);
        self::assertStringContainsString('DB_DATABASE="'.$this->databaseName.'"',$env);
        self::assertStringContainsString('APP_KEY="base64:',$env);
        self::assertStringNotContainsString($data->adminPassword,$env);

        $lock=json_decode(
            (string)file_get_contents($this->root().'/storage/installed.lock'),
            true,
            16,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame(Version::CURRENT,$lock['version']??null);
        self::assertNotEmpty($lock['installed_at']??null);

        $database=$this->database();
        self::assertSame(
            Version::CURRENT,
            (string)$database->query("SELECT `value` FROM settings WHERE `key`='system.core_version'")->fetchColumn(),
        );
        self::assertSame(
            1,
            (int)$database->query("SELECT COUNT(*) FROM users WHERE username='rc_admin' AND status='active'")->fetchColumn(),
        );
        self::assertSame(
            1,
            (int)$database->query(
                "SELECT COUNT(*) FROM user_roles ur "
                ."JOIN users u ON u.id=ur.user_id "
                ."JOIN roles r ON r.id=ur.role_id "
                ."WHERE u.username='rc_admin' AND r.slug='super-administrator'"
            )->fetchColumn(),
        );

        $expected=count(glob(dirname(__DIR__,2).'/database/migrations/*.php')?:[]);
        self::assertSame(
            $expected,
            (int)$database->query('SELECT COUNT(*) FROM migrations')->fetchColumn(),
        );
        self::assertSame(
            $expected,
            (int)$database->query("SELECT COUNT(*) FROM migration_operations WHERE scope='core' AND direction='up' AND state='completed'")->fetchColumn(),
        );

        foreach([
            'storage/cache',
            'storage/logs',
            'storage/sessions',
            'storage/private/downloads',
            'storage/private/backups',
            'storage/private/avatars',
            'storage/private/wiki',
            'public/uploads',
        ] as $directory){
            self::assertDirectoryExists($this->root().'/'.$directory,$directory);
        }
    }

    public function testInstallerAcceptsPreExistingEmptyDatabase(): void
    {
        $this->server?->exec(
            "CREATE DATABASE `{$this->databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $migrations=(new InstallerService($this->root(),new EnvWriter()))->install($this->data());

        self::assertNotEmpty($migrations);
        self::assertFileExists($this->root().'/.env');
        self::assertFileExists($this->root().'/storage/installed.lock');
        self::assertSame(1,(int)$this->database()->query("SELECT COUNT(*) FROM users WHERE username='rc_admin'")->fetchColumn());
    }

    public function testFailedFreshMigrationCleansRecoveryLedgerAndCanRetryFromEmptySchema(): void
    {
        $failure=$this->root().'/database/migrations/2099_01_01_000001_fail_after_ddl.php';
        file_put_contents($failure, <<<'PHP'
<?php
use NovaNuke\Core\Database\MigrationSchema;
use NovaNuke\Core\Database\RecoverableMigration;
return new class implements RecoverableMigration {
    public function up(PDO $database):void{$database->exec('CREATE TABLE IF NOT EXISTS installer_failure_probe (id INT PRIMARY KEY) ENGINE=InnoDB');throw new RuntimeException('Injected installer migration failure.');}
    public function down(PDO $database):void{$database->exec('DROP TABLE IF EXISTS installer_failure_probe');}
    public function isApplied(PDO $database):bool{return MigrationSchema::tableExists($database,'installer_failure_probe');}
    public function isRolledBack(PDO $database):bool{return !MigrationSchema::tableExists($database,'installer_failure_probe');}
};
PHP);

        try {
            (new InstallerService($this->root(),new EnvWriter()))->install($this->data());
            self::fail('The injected migration should fail the fresh installation.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('Core migration failed', $error->getMessage());
        }

        $database=$this->database();
        self::assertSame(0,(int)$database->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn());
        self::assertFileDoesNotExist($this->root().'/.env');
        self::assertFileDoesNotExist($this->root().'/storage/installed.lock');

        unlink($failure);
        $completed=(new InstallerService($this->root(),new EnvWriter()))->install($this->data());
        self::assertNotEmpty($completed);
        self::assertFileExists($this->root().'/storage/installed.lock');
    }

    public function testInstallerRefusesNonEmptyDatabaseWithoutCreatingEnvironmentOrLock(): void
    {
        $this->server?->exec(
            "CREATE DATABASE `{$this->databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $database=$this->database();
        $database->exec('CREATE TABLE foreign_existing_data (id INT PRIMARY KEY)');

        try{
            (new InstallerService($this->root(),new EnvWriter()))->install($this->data());
            self::fail('Installer should reject a non-empty database.');
        }catch(RuntimeException $error){
            self::assertStringContainsString('database is not empty',$error->getMessage());
        }

        self::assertFileDoesNotExist($this->root().'/.env');
        self::assertFileDoesNotExist($this->root().'/storage/installed.lock');
        self::assertSame(
            1,
            (int)$database->query(
                "SELECT COUNT(*) FROM information_schema.tables "
                ."WHERE table_schema=DATABASE() AND table_name='foreign_existing_data'"
            )->fetchColumn(),
            'Pre-existing database content must never be deleted by a refused installation.',
        );
    }

    private function data(): InstallationData
    {
        return new InstallationData(
            siteName:'NovaNuke RC Installer Test',
            siteUrl:'https://installer.example.test',
            locale:'en',
            timezone:'UTC',
            databaseHost:(string)env('NOVANUKE_TEST_DB_HOST','127.0.0.1'),
            databasePort:(int)env('NOVANUKE_TEST_DB_PORT','3306'),
            databaseName:(string)$this->databaseName,
            databaseUsername:(string)env('NOVANUKE_TEST_DB_USERNAME','root'),
            databasePassword:(string)env('NOVANUKE_TEST_DB_PASSWORD',''),
            adminUsername:'rc_admin',
            adminEmail:'rc-admin@example.test',
            adminPassword:'Installer-Test-Password-92!',
        );
    }

    private function database(): PDO
    {
        $host=(string)env('NOVANUKE_TEST_DB_HOST','127.0.0.1');
        $port=(int)env('NOVANUKE_TEST_DB_PORT','3306');
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$this->databaseName};charset=utf8mb4",
            (string)env('NOVANUKE_TEST_DB_USERNAME','root'),
            (string)env('NOVANUKE_TEST_DB_PASSWORD',''),
            [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false,
            ],
        );
    }

    private function root(): string
    {
        return $this->root??throw new RuntimeException('Installer test root is unavailable.');
    }

    private function removeTree(string $root): void
    {
        $iterator=new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach($iterator as $item){
            $item->isDir()?@rmdir($item->getPathname()):@unlink($item->getPathname());
        }
        @rmdir($root);
    }
}
