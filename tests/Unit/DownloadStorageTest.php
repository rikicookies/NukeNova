<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Downloads\src\DownloadStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DownloadStorageTest extends TestCase
{
    public function testItRejectsStoredNamesThatCouldTraverseDirectories(): void
    {
        $this->expectException(RuntimeException::class);
        (new DownloadStorage(sys_get_temp_dir()))->path('../secret.zip');
    }

    public function testItRejectsSymlinkedStoredFilesWhenSupported(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || ! function_exists('symlink')) {
            self::markTestSkipped('Reliable symlink creation is not available for this environment.');
        }

        $root = sys_get_temp_dir() . '/novanuke-download-storage-' . bin2hex(random_bytes(4));
        mkdir($root);
        $outside = dirname($root) . '/novanuke-outside-' . bin2hex(random_bytes(4)) . '.zip';
        file_put_contents($outside, 'secret');
        $name = str_repeat('a', 40) . '.zip';
        symlink($outside, $root . '/' . $name);

        try {
            $this->expectException(RuntimeException::class);
            (new DownloadStorage($root))->path($name);
        } finally {
            @unlink($root . '/' . $name);
            @rmdir($root);
            @unlink($outside);
        }
    }
}
