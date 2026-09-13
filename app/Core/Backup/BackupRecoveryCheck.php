<?php

declare(strict_types=1);

namespace NovaNuke\Core\Backup;

use RuntimeException;
use Throwable;

final class BackupRecoveryCheck
{
    public function __construct(
        private readonly string $backupDirectory,
        private readonly BackupVerifier $verifier,
        private readonly FileBackupRestorer $restorer,
    ) {
    }

    /** @return list<array{name:string,passed:bool,detail:string}> */
    public function run(): array
    {
        $checks = [];
        $results = $this->verifier->verifyLatest();
        $byType = [];
        foreach ($results as $result) {
            $byType[$result['type']] = $result;
        }

        foreach (['database', 'files', 'pair'] as $type) {
            $result = $byType[$type] ?? null;
            $passed = is_array($result) && ($result['passed'] ?? false) === true;
            $checks[] = [
                'name' => ucfirst($type) . ' backup verification',
                'passed' => $passed,
                'detail' => is_array($result) ? (string) ($result['detail'] ?? '') : "No {$type} verification result.",
            ];
        }

        $fresh = true;
        $ages = [];
        foreach (['database','files'] as $type) {
            $filename = (string) ($byType[$type]['file'] ?? '');
            $path = $filename !== '' ? rtrim($this->backupDirectory, '/\\') . DIRECTORY_SEPARATOR . $filename : '';
            $mtime = $path !== '' && is_file($path) ? filemtime($path) : false;
            if ($mtime === false) {
                $fresh = false;
                continue;
            }
            $age = max(0, time() - $mtime);
            $ages[] = $age;
            if ($age > 86400) $fresh = false;
        }
        $checks[] = [
            'name' => 'Backup freshness',
            'passed' => $fresh,
            'detail' => $ages === [] ? 'Backup ages are unavailable.' : 'Newest acceptance pair age: ' . max($ages) . ' second(s); maximum 86400.',
        ];

        if (($byType['files']['passed'] ?? false) !== true) {
            $checks[] = [
                'name' => 'Disposable file restore',
                'passed' => false,
                'detail' => 'File backup must verify before extraction can be tested.',
            ];
            return $checks;
        }

        $filename = (string) ($byType['files']['file'] ?? '');
        if ($filename === '' || basename($filename) !== $filename) {
            $checks[] = [
                'name' => 'Disposable file restore',
                'passed' => false,
                'detail' => 'Verified file backup name is invalid.',
            ];
            return $checks;
        }

        $archive = rtrim($this->backupDirectory, '/\\') . DIRECTORY_SEPARATOR . $filename;
        $destination = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'novanuke-restore-check-' . bin2hex(random_bytes(8));

        try {
            $restored = $this->restorer->restore($archive, $destination);
            $checks[] = [
                'name' => 'Disposable file restore',
                'passed' => true,
                'detail' => "{$restored['files']} file(s), {$restored['bytes']} byte(s) restored into a disposable directory and removed.",
            ];
        } catch (Throwable $error) {
            $checks[] = [
                'name' => 'Disposable file restore',
                'passed' => false,
                'detail' => $error->getMessage(),
            ];
        } finally {
            $this->removeTree($destination);
        }

        return $checks;
    }

    public function passed(): bool
    {
        foreach ($this->run() as $check) {
            if (! $check['passed']) return false;
        }
        return true;
    }

    private function removeTree(string $root): void
    {
        if (! is_dir($root)) return;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isLink() || $item->isFile()) @unlink($item->getPathname());
            elseif ($item->isDir()) @rmdir($item->getPathname());
        }
        @rmdir($root);
    }
}
