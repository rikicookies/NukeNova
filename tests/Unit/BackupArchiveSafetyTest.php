<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\BackupVerifier;
use NovaNuke\Core\Backup\FileBackup;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupArchiveSafetyTest extends TestCase
{
    public function testTraversalAbsoluteAndSymlinkEntriesAreRejectedBeforeExtraction(): void
    {
        foreach ([['../escape.txt', '0'], ['/absolute.txt', '0'], ['custom/link.txt', '2']] as [$name, $type]) {
            $root = sys_get_temp_dir().'/novanuke-archive-safety-'.bin2hex(random_bytes(5));
            mkdir($root.'/source',0700,true); mkdir($root.'/backups',0700,true);
            file_put_contents($root.'/source/probe.txt','safe');
            try {
                $archive=(new FileBackup($root,$root.'/backups',['custom'=>$root.'/source']))->create()['path'];
                $stream=fopen($archive,'r+b'); self::assertIsResource($stream);
                $header=fread($stream,512); self::assertIsString($header); self::assertSame(512,strlen($header));
                $header=substr_replace($header,str_pad($name,100,"\0"),0,100);
                $header=substr_replace($header,$type,156,1);
                $header=substr_replace($header,str_repeat(' ',8),148,8);
                $checksum=array_sum(unpack('C*',$header));
                $header=substr_replace($header,sprintf('%06o',$checksum)."\0 ",148,8);
                rewind($stream); fwrite($stream,$header); fclose($stream); @chmod($archive,0600);

                try { (new BackupVerifier($root.'/backups'))->verifyFileArchive($archive); self::fail("Unsafe TAR entry accepted: {$name}"); }
                catch (RuntimeException $error) { self::assertMatchesRegularExpression('/unsafe|non-regular/', $error->getMessage()); }
            } finally { $this->removeTree($root); }
        }
    }

    private function removeTree(string $root): void
    {
        if(!is_dir($root)) return;
        $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);
        foreach($iterator as $item) $item->isDir()&&!$item->isLink()?@rmdir($item->getPathname()):@unlink($item->getPathname());
        @rmdir($root);
    }
}
