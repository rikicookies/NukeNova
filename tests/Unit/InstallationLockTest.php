<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Installer\InstallationLock;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use NovaNuke\Core\Version;

final class InstallationLockTest extends TestCase
{
    public function testItCreatesAVersionedLockWithoutTemporaryResidue(): void
    {
        $directory = sys_get_temp_dir() . '/novanuke-lock-' . bin2hex(random_bytes(5));
        mkdir($directory, 0750);
        $path = $directory . '/installed.lock';
        try {
            (new InstallationLock())->create($path, Version::CURRENT);
            $data = json_decode((string) file_get_contents($path), true, 8, JSON_THROW_ON_ERROR);
            self::assertSame(Version::CURRENT, $data['version']);
            self::assertSame([], glob($directory . '/*.tmp-*') ?: []);
        } finally {
            if (is_file($path)) unlink($path);
            rmdir($directory);
        }
    }

    public function testItRefusesToReplaceAnExistingInstallationLock(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'novanuke-installed-');
        try {
            $this->expectException(RuntimeException::class);
            (new InstallationLock())->create($path, Version::CURRENT);
        } finally {
            if (is_file($path)) unlink($path);
        }
    }
}
