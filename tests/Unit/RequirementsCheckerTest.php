<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Installer\RequirementsChecker;
use PHPUnit\Framework\TestCase;

final class RequirementsCheckerTest extends TestCase
{
    public function testItReportsExistingConfigurationAndInstallationLock(): void
    {
        $root = sys_get_temp_dir() . '/novanuke-requirements-' . bin2hex(random_bytes(5));
        $directories = [
            'storage/cache', 'storage/logs', 'storage/sessions',
            'storage/private/downloads', 'storage/private/backups',
        ];
        foreach ($directories as $directory) mkdir($root . '/' . $directory, 0770, true);

        try {
            $ready = $this->byName((new RequirementsChecker())->check($root));
            self::assertTrue($ready['No existing environment file']['passed']);
            self::assertTrue($ready['No installation lock']['passed']);

            file_put_contents($root . '/.env', "APP_ENV=testing\n");
            file_put_contents($root . '/storage/installed.lock', "{}\n");
            $blocked = $this->byName((new RequirementsChecker())->check($root));
            self::assertFalse($blocked['No existing environment file']['passed']);
            self::assertFalse($blocked['No installation lock']['passed']);
        } finally {
            foreach ([$root . '/.env', $root . '/storage/installed.lock'] as $file) if (is_file($file)) unlink($file);
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) rmdir($item->getPathname());
                else unlink($item->getPathname());
            }
            if (is_dir($root)) rmdir($root);
        }
    }

    /** @return array<string,array{name:string,passed:bool,detail:string}> */
    private function byName(array $checks): array
    {
        $indexed = [];
        foreach ($checks as $check) $indexed[$check['name']] = $check;
        return $indexed;
    }
}
