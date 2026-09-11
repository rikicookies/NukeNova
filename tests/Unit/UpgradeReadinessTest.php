<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\FileBackup;
use NovaNuke\Core\Update\UpgradeReadiness;
use PHPUnit\Framework\TestCase;

final class UpgradeReadinessTest extends TestCase
{
    private string $root;
    private string $databaseBackup;
    private string $fileBackup;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-upgrade-' . bin2hex(random_bytes(5));
        mkdir($this->root . '/storage/private/backups', 0770, true);
        file_put_contents($this->root . '/.env', "APP_ENV=testing\n");
        file_put_contents($this->root . '/storage/installed.lock', json_encode([
            'version' => '0.2.0-alpha.33', 'installed_at' => '2026-09-08T12:00:00+00:00',
        ], JSON_THROW_ON_ERROR));
        mkdir($this->root . '/source', 0770);
        file_put_contents($this->root . '/source/example.txt', 'backup-data');
        $this->databaseBackup = $this->root . '/storage/private/backups/novanuke-db-test.sql';
        file_put_contents($this->databaseBackup, "-- NovaNuke database backup\n-- Created: 2026-09-09T00:00:00+00:00\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\nSET FOREIGN_KEY_CHECKS=1;\n");
        $this->fileBackup = (new FileBackup($this->root, $this->root . '/storage/private/backups', [
            'custom' => $this->root . '/source',
        ]))->create()['path'];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/storage/private/backups/*') ?: [] as $file) unlink($file);
        if (is_file($this->root . '/source/example.txt')) unlink($this->root . '/source/example.txt');
        foreach ([$this->root . '/.env', $this->root . '/storage/installed.lock'] as $file) if (is_file($file)) unlink($file);
        foreach ([$this->root . '/source', $this->root . '/storage/private/backups', $this->root . '/storage/private', $this->root . '/storage', $this->root] as $directory) if (is_dir($directory)) rmdir($directory);
    }

    public function testSupportedUpgradeWithRecentBackupsPassesRequiredChecks(): void
    {
        $checks = $this->checker()->check('0.2.0-alpha.35', $this->status());
        foreach ($checks as $check) {
            if ($check['required']) self::assertTrue($check['passed'], $check['name']);
        }
    }

    public function testUnsupportedSourceAndMissingMigrationFilesBlockUpgrade(): void
    {
        $status = $this->status();
        $status['missing_total'] = 1;
        $checks = $this->byName($this->checker()->check('0.2.0-alpha.20', $status));

        self::assertFalse($checks['Supported source release']['passed']);
        self::assertFalse($checks['Migration files present']['passed']);
        self::assertTrue($checks['Supported source release']['required']);
    }

    public function testStaleBackupsBlockUpgrade(): void
    {
        $old = time() - 90000;
        foreach (glob($this->root . '/storage/private/backups/*') ?: [] as $file) touch($file, $old);
        $checks = $this->byName($this->checker()->check('0.2.0-alpha.35', $this->status()));

        self::assertFalse($checks['Verified database backup']['passed']);
        self::assertFalse($checks['Verified file backup']['passed']);
    }

    public function testMalformedInstallationLockBlocksUpgrade(): void
    {
        file_put_contents($this->root . '/storage/installed.lock', "{}\n");
        $checks = $this->byName($this->checker()->check('0.2.0-alpha.35', $this->status()));
        self::assertFalse($checks['Installation lock']['passed']);
    }

    public function testEmptyBackupFilesBlockUpgrade(): void
    {
        file_put_contents($this->databaseBackup, '');
        file_put_contents($this->fileBackup, '');
        $checks = $this->byName($this->checker()->check('0.2.0-alpha.35', $this->status()));

        self::assertFalse($checks['Verified database backup']['passed']);
        self::assertFalse($checks['Verified file backup']['passed']);
        self::assertFalse($checks['Matched backup pair']['passed']);
    }

    private function checker(): UpgradeReadiness
    {
        return new UpgradeReadiness($this->root, '0.2.0-alpha.39', [
            '0.2.0-alpha.35', '0.2.0-alpha.36', '0.2.0-alpha.37', '0.2.0-alpha.38', '0.2.0-alpha.39',
        ]);
    }

    /** @return array<string,int> */
    private function status(): array
    {
        return ['pending_total' => 2, 'missing_total' => 0, 'module_updates_total' => 1];
    }

    /** @return array<string,array{name:string,passed:bool,required:bool,detail:string}> */
    private function byName(array $checks): array
    {
        $indexed = [];
        foreach ($checks as $check) $indexed[$check['name']] = $check;
        return $indexed;
    }
}
