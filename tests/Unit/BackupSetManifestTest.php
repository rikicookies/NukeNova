<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\BackupVerifier;
use NovaNuke\Core\Backup\FileBackup;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupSetManifestTest extends TestCase
{
    private string $root;
    private string $setId = 'set-20260914120000-0123456789abcdef01234567';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-set-manifest-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/' . $this->setId, 0700, true);
        mkdir($this->root . '/source', 0700, true);
        file_put_contents($this->root . '/source/probe.txt', 'manifest-probe');
    }

    protected function tearDown(): void { $this->removeTree($this->root); }

    public function testCompleteManifestVerifiesBothArtifactsAsOneSet(): void
    {
        $manifest = $this->completeSet();
        $verified = (new BackupVerifier($this->root))->verifyManifest($manifest);

        self::assertSame($this->setId, $verified['manifest']['backup_set_id']);
        self::assertSame($this->setId, $verified['database']['backup_set']);
        self::assertSame($this->setId, $verified['files']['backup_set']);
    }

    public function testInvalidJsonAndUnsupportedFormatAreRejected(): void
    {
        $manifest = $this->root . '/' . $this->setId . '/manifest.json';
        file_put_contents($manifest, '{invalid'); @chmod($manifest, 0600);
        try { (new BackupVerifier($this->root))->verifyManifest($manifest); self::fail('Invalid JSON accepted.'); }
        catch (RuntimeException $error) { self::assertStringContainsString('JSON is invalid', $error->getMessage()); }

        file_put_contents($manifest, json_encode(['format'=>999])); @chmod($manifest, 0600);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('format is unsupported');
        (new BackupVerifier($this->root))->verifyManifest($manifest);
    }

    public function testMissingArtifactAndWrongChecksumFailClosed(): void
    {
        $manifest = $this->completeSet();
        $record = json_decode((string) file_get_contents($manifest), true, 32, JSON_THROW_ON_ERROR);
        $database = dirname($manifest) . '/' . $record['components']['database']['name'];
        unlink($database);
        try { (new BackupVerifier($this->root))->verifyManifest($manifest); self::fail('Missing SQL accepted.'); }
        catch (RuntimeException $error) { self::assertStringContainsString('regular readable file', $error->getMessage()); }

        $manifest = $this->completeSet();
        $record = json_decode((string) file_get_contents($manifest), true, 32, JSON_THROW_ON_ERROR);
        $record['components']['files']['sha256'] = str_repeat('0', 64);
        file_put_contents($manifest, json_encode($record)); @chmod($manifest, 0600);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('size or checksum does not match');
        (new BackupVerifier($this->root))->verifyManifest($manifest);
    }

    public function testComponentSetIdMismatchIsRejected(): void
    {
        $manifest = $this->completeSet();
        $record = json_decode((string) file_get_contents($manifest), true, 32, JSON_THROW_ON_ERROR);
        $record['components']['database']['backup_set_id'] = 'set-20260914120001-aaaaaaaaaaaaaaaaaaaaaaaa';
        file_put_contents($manifest, json_encode($record)); @chmod($manifest, 0600);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('component metadata is invalid');
        (new BackupVerifier($this->root))->verifyManifest($manifest);
    }

    private function completeSet(): string
    {
        $directory = $this->root . '/' . $this->setId;
        foreach (glob($directory . '/*') ?: [] as $path) if (is_file($path)) unlink($path);
        $sql = $directory . '/novanuke-db-test.sql';
        file_put_contents($sql, "-- NovaNuke database backup\n-- Created: 2026-09-14T12:00:00+00:00\n-- Format: 2\n-- Backup-Set: {$this->setId}\n-- Snapshot: consistent-inno-db\n" . str_repeat("-- filler\n", 8) . "SET FOREIGN_KEY_CHECKS=1;\n");
        @chmod($sql, 0600);
        $files = (new FileBackup($this->root, $directory, ['custom'=>$this->root.'/source']))->create($this->setId);
        $manifest = $directory . '/manifest.json';
        $record = [
            'format'=>1, 'backup_set_id'=>$this->setId, 'status'=>'complete', 'cms_version'=>'0.4.0-rc.3', 'php_version'=>PHP_VERSION,
            'started_at'=>'2026-09-14T12:00:00+00:00', 'completed_at'=>'2026-09-14T12:00:01+00:00',
            'components'=>[
                'database'=>['name'=>basename($sql),'bytes'=>filesize($sql),'sha256'=>hash_file('sha256',$sql),'backup_set_id'=>$this->setId],
                'files'=>['name'=>basename($files['path']),'bytes'=>filesize($files['path']),'sha256'=>hash_file('sha256',$files['path']),'backup_set_id'=>$this->setId],
            ],
        ];
        file_put_contents($manifest, json_encode($record, JSON_THROW_ON_ERROR)); @chmod($manifest, 0600);
        return $manifest;
    }

    private function removeTree(string $root): void
    {
        if (! is_dir($root)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() && ! $item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        @rmdir($root);
    }
}
