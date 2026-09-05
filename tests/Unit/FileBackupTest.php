<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\FileBackup;
use PHPUnit\Framework\TestCase;

final class FileBackupTest extends TestCase
{
    public function testItCreatesPrivateTarWithManifestAndSkipsSymbolicLinks(): void
    {
        $root = sys_get_temp_dir() . '/novanuke-files-' . bin2hex(random_bytes(5));
        $source = $root . '/source';
        $backups = $root . '/backups';
        mkdir($source, 0700, true);
        file_put_contents($source . '/example.txt', 'backup-data');
        if (function_exists('symlink')) @symlink($source . '/example.txt', $source . '/linked.txt');

        try {
            $result = (new FileBackup($root, $backups, ['custom' => $source]))->create();
            self::assertFileExists($result['path']);
            self::assertSame(1, $result['files']);
            self::assertSame(11, $result['bytes']);
            self::assertSame(hash_file('sha256', $result['path']), $result['sha256']);
            $entries = $this->entries($result['path']);
            self::assertSame('backup-data', $entries['custom/example.txt']);
            self::assertArrayNotHasKey('custom/linked.txt', $entries);
            $manifest = json_decode($entries['NOVANUKE-BACKUP.json'], true, 16, JSON_THROW_ON_ERROR);
            self::assertSame(hash('sha256', 'backup-data'), $manifest['files'][0]['sha256']);
        } finally {
            if (is_link($source . '/linked.txt')) unlink($source . '/linked.txt');
            foreach (glob($backups . '/*') ?: [] as $file) unlink($file);
            if (is_file($source . '/example.txt')) unlink($source . '/example.txt');
            if (is_dir($source)) rmdir($source);
            if (is_dir($backups)) rmdir($backups);
            if (is_dir($root)) rmdir($root);
        }
    }

    /** @return array<string,string> */
    private function entries(string $path): array
    {
        $stream = fopen($path, 'rb');
        self::assertIsResource($stream);
        $entries = [];
        while (($header = fread($stream, 512)) !== false && strlen($header) === 512 && trim($header, "\0") !== '') {
            $name = rtrim(substr($header, 0, 100), "\0");
            $prefix = rtrim(substr($header, 345, 155), "\0");
            $size = octdec(trim(substr($header, 124, 12), "\0 "));
            $content = $size > 0 ? fread($stream, $size) : '';
            self::assertIsString($content);
            $entries[$prefix === '' ? $name : $prefix . '/' . $name] = $content;
            $padding = (512 - ($size % 512)) % 512;
            if ($padding > 0) fread($stream, $padding);
        }
        fclose($stream);
        return $entries;
    }
}
