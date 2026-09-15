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
        $setId = 'set-20260912190000-0123456789abcdef01234567';
        $this->databaseBackup = $this->root . '/storage/private/backups/novanuke-db-test.sql';
        file_put_contents($this->databaseBackup, "-- NovaNuke database backup\n-- Created: 2026-09-09T00:00:00+00:00\n-- Format: 2\n-- Backup-Set: {$setId}\n-- Snapshot: consistent-inno-db\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\nSET FOREIGN_KEY_CHECKS=1;\n");
        @chmod($this->databaseBackup, 0600);
        $this->fileBackup = (new FileBackup($this->root, $this->root . '/storage/private/backups', [
            'custom' => $this->root . '/source',
        ]))->create($setId)['path'];
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
        $checks = $this->checker()->check('0.2.0-alpha.35', $this->upgradeStatus());
        foreach ($checks as $check) {
            if ($check['required']) self::assertTrue($check['passed'], $check['name']);
        }
    }

    public function testUnsupportedSourceAndMissingMigrationFilesBlockUpgrade(): void
    {
        $status = $this->upgradeStatus();
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
        $checks = $this->byName($this->checker()->check('0.2.0-alpha.35', $this->upgradeStatus()));

        self::assertFalse($checks['Verified database backup']['passed']);
        self::assertFalse($checks['Verified file backup']['passed']);
    }

    public function testMalformedInstallationLockBlocksUpgrade(): void
    {
        file_put_contents($this->root . '/storage/installed.lock', "{}\n");
        $checks = $this->byName($this->checker()->check('0.2.0-alpha.35', $this->upgradeStatus()));
        self::assertFalse($checks['Installation lock']['passed']);
    }

    public function testInterruptedMigrationBlocksUpgradePreflight(): void
    {
        $status = $this->upgradeStatus();
        $status['recovery_total'] = 1;
        $check = $this->byName($this->checker()->check('0.2.0-alpha.35', $status))['No interrupted migration'];

        self::assertTrue($check['required']);
        self::assertFalse($check['passed']);
    }

    public function testEmptyBackupFilesBlockUpgrade(): void
    {
        file_put_contents($this->databaseBackup, '');
        file_put_contents($this->fileBackup, '');
        $checks = $this->byName($this->checker()->check('0.2.0-alpha.35', $this->upgradeStatus()));

        self::assertFalse($checks['Verified database backup']['passed']);
        self::assertFalse($checks['Verified file backup']['passed']);
        self::assertFalse($checks['Matched backup pair']['passed']);
    }

    public function testRecordedSourceMustMatchTheDeclaredSource(): void
    {
        $checks = $this->byName($this->checker()->check(
            '0.2.0-alpha.38',
            $this->upgradeStatus(),
            recordedVersion: '0.2.0-alpha.37',
        ));

        self::assertTrue($checks['Recorded source version']['required']);
        self::assertFalse($checks['Recorded source version']['passed']);
    }

    private function checker(): UpgradeReadiness
    {
        return new UpgradeReadiness($this->root, '0.4.0-rc.3', [
            '0.2.0-alpha.35', '0.2.0-alpha.36', '0.2.0-alpha.37', '0.2.0-alpha.38', '0.2.0-alpha.39', '0.2.0-alpha.40', '0.2.0-alpha.41', '0.2.0-alpha.42', '0.2.0-alpha.43', '0.2.0-alpha.44', '0.2.0-alpha.45', '0.2.0-alpha.46', '0.2.0-alpha.47', '0.2.0-alpha.48', '0.2.0-alpha.49', '0.2.0-alpha.50', '0.2.0-alpha.51', '0.2.0-alpha.52', '0.2.0-alpha.53', '0.2.0-alpha.54', '0.2.0-alpha.55', '0.2.0-alpha.56', '0.2.0-alpha.57', '0.2.0-alpha.58', '0.2.0-alpha.59', '0.2.0-alpha.60', '0.3.0-beta.1', '0.3.0-beta.2', '0.3.0-beta.3', '0.3.0-beta.4', '0.3.0-beta.5', '0.3.0-beta.6', '0.4.0-beta.1', '0.4.0-beta.2', '0.4.0-beta.3', '0.4.0-beta.4', '0.4.0-beta.5', '0.4.0-beta.6', '0.4.0-beta.7', '0.4.0-beta.8', '0.4.0-beta.9', '0.4.0-beta.10', '0.4.0-beta.11', '0.4.0-beta.12', '0.4.0-beta.13', '0.4.0-beta.14', '0.4.0-beta.15', '0.4.0-beta.16', '0.4.0-beta.17', '0.4.0-beta.18', '0.4.0-beta.19', '0.4.0-beta.20', '0.4.0-beta.21', '0.4.0-beta.22', '0.4.0-beta.23', '0.4.0-beta.24', '0.4.0-beta.25', '0.4.0-beta.26', '0.4.0-beta.27', '0.4.0-beta.28', '0.4.0-rc.1', '0.4.0-rc.2',
        ]);
    }

    /** @return array<string,int> */
    private function upgradeStatus(): array
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
