<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Installer\RequirementsChecker;
use NovaNuke\Installer\StorageProvisioner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InstallerStorageProvisioningTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-storage-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testProvisionerCreatesEveryRequiredRuntimeDirectory(): void
    {
        $provisioner = new StorageProvisioner();
        $provisioner->provision($this->root);

        foreach ($provisioner->requiredDirectories() as $directory) {
            self::assertDirectoryExists($this->root . '/' . $directory);
            self::assertTrue(is_writable($this->root . '/' . $directory), $directory);
        }
    }

    public function testInstallCheckCreatesMissingStorageBeforeTestingWritability(): void
    {
        $checks = (new RequirementsChecker())->check($this->root);
        $byName = [];
        foreach ($checks as $check) $byName[$check['name']] = $check;

        foreach ((new StorageProvisioner())->requiredDirectories() as $directory) {
            self::assertTrue($byName["Writable {$directory}"]['passed'], $directory);
        }
    }

    public function testProvisionerRejectsSymlinkedStorageBoundary(): void
    {
        if (! function_exists('symlink') || PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('Reliable symlink creation is not available for this test environment.');
        }

        mkdir($this->root . '/outside', 0770, true);
        mkdir($this->root . '/storage/private', 0770, true);
        symlink($this->root . '/outside', $this->root . '/storage/private/downloads');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('symbolic link');
        (new StorageProvisioner())->provision($this->root);
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);
            return;
        }
        if (! is_dir($path)) return;
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $this->remove($path . DIRECTORY_SEPARATOR . $entry);
        }
        @rmdir($path);
    }
}
