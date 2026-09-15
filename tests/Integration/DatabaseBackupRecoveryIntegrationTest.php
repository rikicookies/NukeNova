<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Backup\BackupVerifier;
use NovaNuke\Core\Backup\DatabaseBackup;
use NovaNuke\Core\Backup\DatabaseRestoreVerifier;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use PDO;
use RuntimeException;

final class DatabaseBackupRecoveryIntegrationTest extends MySqlIntegrationTestCase
{
    private ?string $directory = null;

    protected function tearDown(): void
    {
        if ($this->directory !== null && is_dir($this->directory)) {
            foreach (glob($this->directory . '/*') ?: [] as $file) if (is_file($file)) @unlink($file);
            @rmdir($this->directory);
        }
        $this->directory = null;
        parent::tearDown();
    }

    public function testConsistentSqlBackupCanBeImportedIntoDisposableEmptyDatabaseAndCleaned(): void
    {
        $this->directory = sys_get_temp_dir() . '/novanuke-db-restore-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
        $this->db()->exec("CREATE TABLE backup_probe (id INT PRIMARY KEY, payload VARCHAR(80) NOT NULL) ENGINE=InnoDB");
        $this->db()->exec("INSERT INTO backup_probe (id,payload) VALUES (1,'semi;colon and quote \\' value')");
        $setId = 'set-20260912190000-0123456789abcdef01234567';
        $path = (new DatabaseBackup($this->db(), $this->directory))->create($setId);

        $verified = (new BackupVerifier($this->directory))->verifyDatabase($path);
        self::assertTrue($verified['snapshot_consistent']);
        self::assertSame($setId, $verified['backup_set']);

        $this->emptyCurrentDatabase();
        $result = (new DatabaseRestoreVerifier($this->db()))->verify($path);
        self::assertGreaterThan(0, $result['statements']);
        self::assertGreaterThan(1, $result['tables']);
        self::assertGreaterThan(0, $result['migrations']);
        self::assertSame(0, $this->currentTableCount(), 'Disposable verifier must clean restored tables.');
    }

    public function testStructurallyCompleteButInvalidSqlFailsRealRestoreVerification(): void
    {
        $this->directory = sys_get_temp_dir() . '/novanuke-db-corrupt-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
        $path = (new DatabaseBackup($this->db(), $this->directory))->create('set-20260912190000-0123456789abcdef01234567');
        $sql = (string) file_get_contents($path);
        $sql = str_replace("SET FOREIGN_KEY_CHECKS=1;\n", "THIS IS NOT SQL;\nSET FOREIGN_KEY_CHECKS=1;\n", $sql);
        file_put_contents($path, $sql);
        @chmod($path, 0600);

        $integrity = (new BackupVerifier($this->directory))->verifyDatabase($path);
        self::assertSame(1, $integrity['files'], 'File integrity inspection alone cannot prove restorability.');
        $this->emptyCurrentDatabase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Disposable SQL restore failed');
        (new DatabaseRestoreVerifier($this->db()))->verify($path);
    }

    private function emptyCurrentDatabase(): void
    {
        $this->db()->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->db()->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $identifier = '`' . str_replace('`', '``', (string) $table) . '`';
            $this->db()->exec("DROP TABLE {$identifier}");
        }
        $this->db()->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function currentTableCount(): int
    {
        return (int) $this->db()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'")->fetchColumn();
    }
}
