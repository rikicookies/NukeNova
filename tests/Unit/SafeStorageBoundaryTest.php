<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Storage\SafeStorageBoundary;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SafeStorageBoundaryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-boundary-' . bin2hex(random_bytes(5));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testExistingFileMustRemainInsideTheStorageRoot(): void
    {
        $storage = $this->root . '/private';
        mkdir($storage);
        file_put_contents($storage . '/safe.bin', 'safe');

        self::assertSame(
            realpath($storage . '/safe.bin'),
            SafeStorageBoundary::existingFile($storage, 'safe.bin'),
        );
    }

    public function testTraversalCannotEscapeTheStorageRoot(): void
    {
        $storage = $this->root . '/private';
        mkdir($storage);
        file_put_contents($this->root . '/secret.bin', 'secret');

        $this->expectException(RuntimeException::class);
        SafeStorageBoundary::existingFile($storage, '../secret.bin');
    }

    public function testSymlinkedFileIsRejectedWhenSupported(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || ! function_exists('symlink')) {
            self::markTestSkipped('Reliable symlink creation is not available for this environment.');
        }

        $storage = $this->root . '/private';
        mkdir($storage);
        file_put_contents($this->root . '/secret.bin', 'secret');
        symlink($this->root . '/secret.bin', $storage . '/safe.bin');

        $this->expectException(RuntimeException::class);
        SafeStorageBoundary::existingFile($storage, 'safe.bin');
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
