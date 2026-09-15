<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\BackupRecoveryCheck;
use NovaNuke\Core\Backup\BackupVerifier;
use NovaNuke\Core\Backup\FileBackup;
use NovaNuke\Core\Backup\FileBackupRestorer;
use PHPUnit\Framework\TestCase;

final class BackupRecoveryCheckTest extends TestCase
{
    private string $root;
    private string $setId = 'set-20260912190000-0123456789abcdef01234567';

    protected function setUp(): void
    {
        $this->root=sys_get_temp_dir().'/novanuke-recovery-check-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/storage/private/backups',0700,true);
        mkdir($this->root.'/source',0700,true);
        file_put_contents($this->root.'/source/example.txt','recover-me');
        $database=$this->root.'/storage/private/backups/novanuke-db-20260911-120000-test.sql';
        file_put_contents($database, "-- NovaNuke database backup\n-- Created: 2026-09-11T12:00:00+00:00\n-- Format: 2\n-- Backup-Set: {$this->setId}\n-- Snapshot: consistent-inno-db\n".str_repeat("-- filler\n",8)."SET FOREIGN_KEY_CHECKS=1;\n");
        @chmod($database,0600);
        (new FileBackup($this->root,$this->root.'/storage/private/backups',['custom'=>$this->root.'/source']))->create($this->setId);
    }

    protected function tearDown(): void { $this->removeTree($this->root); }

    public function testVerifiedSetRestoresFilesButDoesNotPretendSqlWasImportedWithoutRunner(): void
    {
        $directory=$this->root.'/storage/private/backups';
        $verifier=new BackupVerifier($directory);
        $results=(new BackupRecoveryCheck($directory,$verifier,new FileBackupRestorer($verifier)))->run();
        $byName=[]; foreach($results as $item) $byName[$item['name']]=$item;
        self::assertTrue($byName['Database backup verification']['passed']);
        self::assertTrue($byName['Files backup verification']['passed']);
        self::assertTrue($byName['Pair backup verification']['passed']);
        self::assertTrue($byName['Database snapshot consistency']['passed']);
        self::assertFalse($byName['Disposable database restore']['passed']);
        self::assertStringContainsString('MANUAL REQUIRED / NOT VERIFIED', $byName['Disposable database restore']['detail']);
        self::assertTrue($byName['Disposable file restore']['passed']);
    }

    private function removeTree(string $root): void
    {
        if(!is_dir($root)) return;
        $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);
        foreach($iterator as $item) $item->isDir()?@rmdir($item->getPathname()):@unlink($item->getPathname());
        @rmdir($root);
    }
}
