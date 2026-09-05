<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Downloads\src\DownloadOrphanCleaner;
use PHPUnit\Framework\TestCase;

final class DownloadOrphanCleanerTest extends TestCase
{
    public function testDryRunAndExplicitDeletionRespectReferencesAndGracePeriod(): void
    {
        $directory = sys_get_temp_dir() . '/novanuke-orphans-' . bin2hex(random_bytes(5));
        mkdir($directory, 0700, true);
        $referenced = str_repeat('a', 40) . '.zip';
        $orphan = str_repeat('b', 40) . '.zip';
        $recent = str_repeat('c', 40) . '.pdf';
        file_put_contents($directory . '/' . $referenced, 'kept');
        file_put_contents($directory . '/' . $orphan, 'orphan');
        file_put_contents($directory . '/' . $recent, 'new');
        touch($directory . '/' . $referenced, 1000);
        touch($directory . '/' . $orphan, 1000);
        touch($directory . '/' . $recent, 1990);

        try {
            $cleaner = new DownloadOrphanCleaner($directory, static fn (): array => [$referenced], 100);
            self::assertSame(['eligible' => 1, 'bytes' => 6, 'removed' => 0, 'recent' => 1], $cleaner->run(false, 2000));
            self::assertFileExists($directory . '/' . $orphan);
            self::assertSame(['eligible' => 1, 'bytes' => 6, 'removed' => 1, 'recent' => 1], $cleaner->run(true, 2000));
            self::assertFileDoesNotExist($directory . '/' . $orphan);
            self::assertFileExists($directory . '/' . $referenced);
            self::assertFileExists($directory . '/' . $recent);
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) unlink($file);
            if (is_dir($directory)) rmdir($directory);
        }
    }
}
