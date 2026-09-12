<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\BackupVerifier;
use NovaNuke\Core\Backup\FileBackup;
use PHPUnit\Framework\TestCase;

final class BackupVerifierTest extends TestCase
{
    private string $root;
    private string $backups;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-verify-' . bin2hex(random_bytes(5));
        $this->backups = $this->root . '/backups';
        mkdir($this->root . '/source', 0700, true);
        mkdir($this->backups, 0700, true);
        file_put_contents($this->root . '/source/example.txt', 'backup-data');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->backups . '/*') ?: [] as $file) if (is_file($file) || is_link($file)) unlink($file);
        if (is_file($this->root . '/source/example.txt')) unlink($this->root . '/source/example.txt');
        foreach ([$this->root . '/source', $this->backups, $this->root] as $directory) if (is_dir($directory)) rmdir($directory);
    }

    public function testItVerifiesTheLatestDatabaseAndFileBackups(): void
    {
        $this->databaseBackup();
        (new FileBackup($this->root, $this->backups, ['custom' => $this->root . '/source']))->create();

        $results = (new BackupVerifier($this->backups))->verifyLatest();

        self::assertTrue($results[0]['passed'], $results[0]['detail']);
        self::assertTrue($results[1]['passed'], $results[1]['detail']);
        self::assertTrue($results[2]['passed'], $results[2]['detail']);
        self::assertStringContainsString('SHA-256', $results[0]['detail']);
        self::assertStringContainsString('1 file(s)', $results[1]['detail']);
    }

    public function testItRejectsTruncatedSqlAndTamperedTarContent(): void
    {
        $database = $this->databaseBackup();
        $archive = (new FileBackup($this->root, $this->backups, ['custom' => $this->root . '/source']))->create()['path'];
        file_put_contents($database, "-- NovaNuke database backup\n-- Created: damaged\n");
        $contents = (string) file_get_contents($archive);
        file_put_contents($archive, str_replace('backup-data', 'backup-dAta', $contents));

        $results = (new BackupVerifier($this->backups))->verifyLatest();

        self::assertFalse($results[0]['passed']);
        self::assertFalse($results[1]['passed']);
    }

    public function testCliExposesOnlyLatestBackupVerificationWithoutPathArguments(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        $start = strpos($source, "if (\$command === 'backup:verify')");
        $end = strpos($source, "if (\$command === 'help')", $start);
        $section = substr($source, $start, $end - $start);

        self::assertStringContainsString('count($argv) !== 2', $section);
        self::assertStringContainsString('BackupVerifier', $section);
        self::assertStringNotContainsString('$argv[2]', $section);
    }

    public function testItRejectsDataAppendedAfterTheTarTerminator(): void
    {
        $this->databaseBackup();
        $archive = (new FileBackup($this->root, $this->backups, ['custom' => $this->root . '/source']))->create()['path'];
        file_put_contents($archive, 'unexpected', FILE_APPEND);

        $results = (new BackupVerifier($this->backups))->verifyLatest();

        self::assertFalse($results[1]['passed']);
        self::assertStringContainsString('after its TAR terminator', $results[1]['detail']);
    }

    public function testItRejectsBackupsCreatedTooFarApartToBeAMatchedPair(): void
    {
        $this->databaseBackup();
        $archive = (new FileBackup($this->root, $this->backups, ['custom' => $this->root . '/source']))->create()['path'];
        touch($archive, time() - 601);

        $results = (new BackupVerifier($this->backups))->verifyLatest();

        self::assertTrue($results[0]['passed']);
        self::assertTrue($results[1]['passed']);
        self::assertFalse($results[2]['passed']);
        self::assertStringContainsString('fresh matched pair', $results[2]['detail']);
    }


    public function testItRejectsOverlyPermissiveBackupPermissionsOnPosix(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('POSIX permission bits are not reliable on Windows.');
        }

        $database = $this->databaseBackup();
        @chmod($database, 0644);
        (new FileBackup($this->root, $this->backups, ['custom' => $this->root . '/source']))->create();

        $results = (new BackupVerifier($this->backups))->verifyLatest();

        self::assertFalse($results[0]['passed']);
        self::assertStringContainsString('permissions are too permissive', $results[0]['detail']);
    }

    private function databaseBackup(): string
    {
        $path = $this->backups . '/novanuke-db-test.sql';
        file_put_contents($path, "-- NovaNuke database backup\n-- Created: 2026-09-09T00:00:00+00:00\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\nSET FOREIGN_KEY_CHECKS=1;\n");
        @chmod($path, 0600);
        return $path;
    }
}
