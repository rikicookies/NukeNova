<?php

declare(strict_types=1);

namespace NovaNuke\Core\Backup;

use RuntimeException;
use Throwable;

final class BackupVerifier
{
    public function __construct(private readonly string $directory)
    {
    }

    /** @return list<array{type:string,passed:bool,file:string,detail:string}> */
    public function verifyLatest(): array
    {
        $databasePath = $this->latest('novanuke-db-*.sql');
        $filePath = $this->latest('novanuke-files-*.tar');
        $database = $this->verifyCandidate('database', $databasePath, $this->verifyDatabase(...));
        $files = $this->verifyCandidate('files', $filePath, $this->verifyFileArchive(...));

        return [$database, $files, $this->verifyPair($databasePath, $filePath, $database['passed'] && $files['passed'])];
    }

    /** @return array{files:int,bytes:int,sha256:string} */
    public function verifyDatabase(string $path): array
    {
        $this->assertRegularFile($path);
        $size = filesize($path);
        if ($size === false || $size < 64) throw new RuntimeException('Database backup is empty or truncated.');
        $stream = fopen($path, 'rb');
        if ($stream === false) throw new RuntimeException('Database backup is not readable.');
        try {
            $header = fread($stream, 128);
            if (! is_string($header) || ! str_starts_with($header, "-- NovaNuke database backup\n-- Created: ")) {
                throw new RuntimeException('Database backup header is invalid.');
            }
            if (preg_match('/^-- NovaNuke database backup\n-- Created: ([^\r\n]+)\n/', $header, $matches) !== 1
                || strtotime($matches[1]) === false) {
                throw new RuntimeException('Database backup creation date is invalid.');
            }
            if (fseek($stream, max(0, $size - 128)) !== 0) throw new RuntimeException('Database backup cannot be inspected.');
            $tail = stream_get_contents($stream);
            if (! is_string($tail) || ! str_ends_with($tail, "SET FOREIGN_KEY_CHECKS=1;\n")) {
                throw new RuntimeException('Database backup is incomplete.');
            }
        } finally {
            fclose($stream);
        }
        $hash = hash_file('sha256', $path);
        if ($hash === false) throw new RuntimeException('Database backup cannot be fingerprinted.');
        return ['files' => 1, 'bytes' => $size, 'sha256' => $hash];
    }

    /** @return array{files:int,bytes:int,sha256:string} */
    public function verifyFileArchive(string $path): array
    {
        $this->assertRegularFile($path);
        $stream = fopen($path, 'rb');
        if ($stream === false) throw new RuntimeException('File backup is not readable.');
        $entries = [];
        $manifest = null;
        $terminated = false;
        try {
            while (($header = fread($stream, 512)) !== false && $header !== '') {
                if (strlen($header) !== 512) throw new RuntimeException('File backup has a truncated TAR header.');
                if ($header === str_repeat("\0", 512)) {
                    $second = fread($stream, 512);
                    if (! is_string($second) || $second !== str_repeat("\0", 512)) {
                        throw new RuntimeException('File backup has an invalid TAR terminator.');
                    }
                    if (fread($stream, 1) !== '') throw new RuntimeException('File backup contains data after its TAR terminator.');
                    $terminated = true;
                    break;
                }
                $this->assertTarChecksum($header);
                if (substr($header, 257, 6) !== "ustar\0" || substr($header, 263, 2) !== '00') {
                    throw new RuntimeException('File backup does not use the expected USTAR format.');
                }
                if (substr($header, 156, 1) !== '0') throw new RuntimeException('File backup contains a non-regular TAR entry.');
                $name = rtrim(substr($header, 0, 100), "\0");
                $prefix = rtrim(substr($header, 345, 155), "\0");
                $archivePath = $prefix === '' ? $name : $prefix . '/' . $name;
                $this->assertArchivePath($archivePath);
                if (isset($entries[$archivePath]) || ($archivePath === 'NOVANUKE-BACKUP.json' && $manifest !== null)) {
                    throw new RuntimeException("Duplicate backup entry: {$archivePath}");
                }
                $sizeField = trim(substr($header, 124, 12), "\0 ");
                if ($sizeField === '' || preg_match('/^[0-7]+$/', $sizeField) !== 1) {
                    throw new RuntimeException("Invalid TAR size for: {$archivePath}");
                }
                $size = octdec($sizeField);
                $hash = hash_init('sha256');
                $remaining = $size;
                $captured = '';
                if ($archivePath === 'NOVANUKE-BACKUP.json' && $size > 16 * 1024 * 1024) {
                    throw new RuntimeException('Backup manifest is unreasonably large.');
                }
                while ($remaining > 0) {
                    $chunk = fread($stream, min(1048576, $remaining));
                    if (! is_string($chunk) || $chunk === '') throw new RuntimeException("Truncated backup entry: {$archivePath}");
                    hash_update($hash, $chunk);
                    if ($archivePath === 'NOVANUKE-BACKUP.json') $captured .= $chunk;
                    $remaining -= strlen($chunk);
                }
                $padding = (512 - ($size % 512)) % 512;
                if ($padding > 0) {
                    $paddingBytes = fread($stream, $padding);
                    if (! is_string($paddingBytes) || $paddingBytes !== str_repeat("\0", $padding)) {
                        throw new RuntimeException("Invalid TAR padding for: {$archivePath}");
                    }
                }
                if ($archivePath === 'NOVANUKE-BACKUP.json') $manifest = $captured;
                else $entries[$archivePath] = ['bytes' => $size, 'sha256' => hash_final($hash)];
            }
        } finally {
            fclose($stream);
        }
        if (! $terminated) throw new RuntimeException('File backup has no complete TAR terminator.');
        if ($manifest === null) throw new RuntimeException('File backup manifest is missing.');
        $record = json_decode($manifest, true, 16, JSON_THROW_ON_ERROR);
        if (! is_array($record) || ($record['format'] ?? null) !== 1 || ! is_array($record['files'] ?? null)
            || ! is_string($record['created_at'] ?? null) || strtotime($record['created_at']) === false) {
            throw new RuntimeException('File backup manifest is invalid.');
        }
        $expected = [];
        foreach ($record['files'] as $item) {
            if (! is_array($item) || ! is_string($item['path'] ?? null) || ! is_int($item['bytes'] ?? null)
                || $item['bytes'] < 0 || ! is_string($item['sha256'] ?? null)
                || preg_match('/^[a-f0-9]{64}$/', $item['sha256']) !== 1) {
                throw new RuntimeException('File backup manifest contains an invalid entry.');
            }
            $this->assertArchivePath($item['path']);
            if (isset($expected[$item['path']])) throw new RuntimeException("Duplicate manifest entry: {$item['path']}");
            $expected[$item['path']] = ['bytes' => $item['bytes'], 'sha256' => $item['sha256']];
        }
        ksort($entries, SORT_STRING);
        ksort($expected, SORT_STRING);
        if ($entries !== $expected) throw new RuntimeException('File backup contents do not match its manifest.');
        $hash = hash_file('sha256', $path);
        if ($hash === false) throw new RuntimeException('File backup cannot be fingerprinted.');
        return [
            'files' => count($entries),
            'bytes' => array_sum(array_column($entries, 'bytes')),
            'sha256' => $hash,
        ];
    }

    /** @param callable(string):array{files:int,bytes:int,sha256:string} $verifier
     *  @return array{type:string,passed:bool,file:string,detail:string}
     */
    private function verifyCandidate(string $type, ?string $path, callable $verifier): array
    {
        if ($path === null) return ['type' => $type, 'passed' => false, 'file' => '', 'detail' => 'No backup found.'];
        try {
            $result = $verifier($path);
            return [
                'type' => $type,
                'passed' => true,
                'file' => basename($path),
                'detail' => "{$result['files']} file(s), {$result['bytes']} source byte(s), SHA-256 {$result['sha256']}",
            ];
        } catch (Throwable $error) {
            return ['type' => $type, 'passed' => false, 'file' => basename($path), 'detail' => $error->getMessage()];
        }
    }

    /** @return array{type:string,passed:bool,file:string,detail:string} */
    private function verifyPair(?string $databasePath, ?string $filePath, bool $individuallyValid): array
    {
        $file = $databasePath !== null && $filePath !== null
            ? basename($databasePath) . ' + ' . basename($filePath)
            : '';
        if (! $individuallyValid || $databasePath === null || $filePath === null) {
            return ['type' => 'pair', 'passed' => false, 'file' => $file, 'detail' => 'Both backups must pass individually.'];
        }
        $databaseTime = filemtime($databasePath);
        $fileTime = filemtime($filePath);
        if ($databaseTime === false || $fileTime === false) {
            return ['type' => 'pair', 'passed' => false, 'file' => $file, 'detail' => 'Backup creation times are unavailable.'];
        }
        $difference = abs($databaseTime - $fileTime);
        return [
            'type' => 'pair',
            'passed' => $difference <= 600,
            'file' => $file,
            'detail' => $difference <= 600
                ? "Backups were created {$difference} second(s) apart."
                : "Backups were created {$difference} seconds apart; create a fresh matched pair.",
        ];
    }

    private function latest(string $pattern): ?string
    {
        if (! is_dir($this->directory) || is_link($this->directory)) return null;
        $latest = null;
        $latestTime = -1;
        foreach (glob(rtrim($this->directory, '/') . '/' . $pattern) ?: [] as $path) {
            if (! is_file($path) || is_link($path)) continue;
            $modified = filemtime($path);
            if ($modified !== false && $modified > $latestTime) {
                $latest = $path;
                $latestTime = $modified;
            }
        }
        return $latest;
    }

    private function assertRegularFile(string $path): void
    {
        if (! is_file($path) || is_link($path) || ! is_readable($path)) {
            throw new RuntimeException('Backup is not a regular readable file.');
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            $permissions = fileperms($path);
            if ($permissions !== false && (($permissions & 0077) !== 0)) {
                throw new RuntimeException('Backup file permissions are too permissive; expected owner-only access.');
            }
        }
    }

    private function assertTarChecksum(string $header): void
    {
        $field = trim(substr($header, 148, 8), "\0 ");
        if ($field === '' || preg_match('/^[0-7]+$/', $field) !== 1) throw new RuntimeException('TAR checksum is invalid.');
        $unsigned = substr_replace($header, str_repeat(' ', 8), 148, 8);
        if (array_sum(unpack('C*', $unsigned)) !== octdec($field)) throw new RuntimeException('TAR checksum does not match.');
    }

    private function assertArchivePath(string $path): void
    {
        if ($path === '' || str_contains($path, '\\') || preg_match('/[\x00-\x1F\x7F]/', $path) === 1 || str_starts_with($path, '/')
            || preg_match('#(^|/)\.\.(/|$)#', $path) === 1 || preg_match('/^[A-Za-z]:/', $path) === 1) {
            throw new RuntimeException('Backup archive path is unsafe.');
        }
    }
}
