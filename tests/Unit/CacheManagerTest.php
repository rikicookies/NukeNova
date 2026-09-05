<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Cache\CacheManager;
use PHPUnit\Framework\TestCase;

final class CacheManagerTest extends TestCase
{
    public function testItClearsOnlyContentsAndPreservesCacheRoot(): void
    {
        $path = sys_get_temp_dir() . '/novanuke-cache-' . bin2hex(random_bytes(5));
        mkdir($path . '/twig', 0750, true);
        file_put_contents($path . '/twig/compiled.php', 'cache');

        try {
            $manager = new CacheManager($path);
            self::assertSame(2, $manager->clear());
            self::assertDirectoryExists($path);
            self::assertSame(0, $manager->status()['files']);
        } finally {
            if (is_dir($path)) rmdir($path);
        }
    }

    public function testStatusReportsGeneratedFiles(): void
    {
        $path = sys_get_temp_dir() . '/novanuke-cache-' . bin2hex(random_bytes(5));
        mkdir($path, 0750, true);
        file_put_contents($path . '/one.cache', 'cache');
        try {
            self::assertSame(1, (new CacheManager($path))->status()['files']);
        } finally {
            unlink($path . '/one.cache'); rmdir($path);
        }
    }
}
