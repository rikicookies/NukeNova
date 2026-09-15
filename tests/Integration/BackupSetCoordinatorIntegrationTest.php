<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Backup\BackupSetCoordinator;
use NovaNuke\Core\Backup\BackupVerifier;
use NovaNuke\Core\Backup\DatabaseRestoreVerifier;
use NovaNuke\Core\Backup\FileBackupRestorer;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use PDO;
use RuntimeException;

final class BackupSetCoordinatorIntegrationTest extends MySqlIntegrationTestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if ($this->root !== null) $this->removeTree($this->root);
        parent::tearDown();
    }

    public function testBackupSetPublishesOnlyAfterSqlFilesAndManifestVerify(): void
    {
        $this->prepareRoot();
        $this->db()->exec("CREATE TABLE backup_set_probe (id INT PRIMARY KEY, payload VARCHAR(50)) ENGINE=InnoDB");
        $this->db()->exec("INSERT INTO backup_set_probe VALUES (1, 'recoverable-data')");
        $set = (new BackupSetCoordinator($this->db(), $this->root, $this->root.'/storage/private/backups'))->create();

        self::assertFileExists($set['manifest']);
        self::assertFileExists($set['database']);
        self::assertFileExists($set['files']);
        self::assertSame($set['backup_set_id'], basename(dirname($set['manifest'])));
        $verified = (new BackupVerifier($this->root.'/storage/private/backups'))->verifyManifest($set['manifest']);
        self::assertSame($set['backup_set_id'], $verified['manifest']['backup_set_id']);

        $verifier=new BackupVerifier($this->root.'/storage/private/backups');
        $restored=(new FileBackupRestorer($verifier))->restore($set['files'],$this->root.'/restore');
        self::assertGreaterThan(0,$restored['files']);
        self::assertSame('{"name":"probe"}',file_get_contents($this->root.'/restore/modules/example/module.json'));

        $this->emptyCurrentDatabase();
        $database=(new DatabaseRestoreVerifier($this->db()))->verify($set['database']);
        self::assertGreaterThan(0,$database['tables']);
        self::assertSame(0,$this->currentTableCount());
    }

    public function testFaultAfterDatabaseBackupPublishesNoValidSet(): void
    {
        $this->prepareRoot();
        $fault = static function (string $stage): void {
            if ($stage === 'after_database_backup') throw new RuntimeException('Injected backup failure.');
        };
        try {
            (new BackupSetCoordinator($this->db(), $this->root, $this->root.'/storage/private/backups', $fault))->create();
            self::fail('Injected failure was ignored.');
        } catch (RuntimeException $error) {
            self::assertSame('Injected backup failure.', $error->getMessage());
        }
        self::assertSame([], glob($this->root.'/storage/private/backups/set-*/manifest.json') ?: []);
        self::assertSame([], glob($this->root.'/storage/private/backups/.incomplete-*') ?: []);
    }

    private function prepareRoot(): void
    {
        $this->root = sys_get_temp_dir().'/novanuke-coordinator-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/modules/example', 0700, true);
        mkdir($this->root.'/storage/private/backups', 0700, true);
        file_put_contents($this->root.'/modules/example/module.json', '{"name":"probe"}');
    }

    private function emptyCurrentDatabase(): void
    {
        $this->db()->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach($this->db()->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN) as $table){
            $identifier='`'.str_replace('`','``',(string)$table).'`';
            $this->db()->exec("DROP TABLE {$identifier}");
        }
        $this->db()->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function currentTableCount(): int
    {
        return (int)$this->db()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'")->fetchColumn();
    }

    private function removeTree(string $root): void
    {
        if (! is_dir($root)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() && ! $item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        @rmdir($root);
    }
}
