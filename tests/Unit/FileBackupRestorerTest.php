<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\BackupVerifier;
use NovaNuke\Core\Backup\FileBackup;
use NovaNuke\Core\Backup\FileBackupRestorer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FileBackupRestorerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root=sys_get_temp_dir().'/novanuke-restore-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/source/nested',0700,true);
        mkdir($this->root.'/backups',0700,true);
        file_put_contents($this->root.'/source/example.txt','restore-me');
        file_put_contents($this->root.'/source/nested/second.md',"# restored\n");
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testVerifiedArchiveRestoresIntoEmptyDestination(): void
    {
        $backup=(new FileBackup(
            $this->root,
            $this->root.'/backups',
            ['custom'=>$this->root.'/source'],
        ))->create();

        $result=(new FileBackupRestorer(new BackupVerifier($this->root.'/backups')))
            ->restore($backup['path'],$this->root.'/restore');

        self::assertSame(2,$result['files']);
        self::assertSame('restore-me',file_get_contents($this->root.'/restore/custom/example.txt'));
        self::assertSame("# restored\n",file_get_contents($this->root.'/restore/custom/nested/second.md'));
    }

    public function testRestoreRefusesNonEmptyDestinationWithoutOverwritingData(): void
    {
        $backup=(new FileBackup(
            $this->root,
            $this->root.'/backups',
            ['custom'=>$this->root.'/source'],
        ))->create();
        mkdir($this->root.'/restore',0700,true);
        file_put_contents($this->root.'/restore/existing.txt','keep-me');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be empty');
        try{
            (new FileBackupRestorer(new BackupVerifier($this->root.'/backups')))
                ->restore($backup['path'],$this->root.'/restore');
        }finally{
            self::assertSame('keep-me',file_get_contents($this->root.'/restore/existing.txt'));
        }
    }

    public function testTamperedArchiveIsRejectedBeforeDestinationIsCreated(): void
    {
        $backup=(new FileBackup(
            $this->root,
            $this->root.'/backups',
            ['custom'=>$this->root.'/source'],
        ))->create();
        file_put_contents($backup['path'],'corrupt',FILE_APPEND);

        $this->expectException(RuntimeException::class);
        try{
            (new FileBackupRestorer(new BackupVerifier($this->root.'/backups')))
                ->restore($backup['path'],$this->root.'/restore');
        }finally{
            self::assertDirectoryDoesNotExist($this->root.'/restore');
        }
    }

    private function removeTree(string $root): void
    {
        if(!is_dir($root)) return;
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
