<?php

declare(strict_types=1);

namespace NovaNuke\Installer;

final class RequirementsChecker
{
    /** @return list<array{name: string, passed: bool, detail: string}> */
    public function check(string $rootPath): array
    {
        $checks = [[
            'name' => 'PHP 8.3+',
            'passed' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'detail' => 'Detected ' . PHP_VERSION,
        ]];

        foreach (['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'fileinfo'] as $extension) {
            $checks[] = [
                'name' => "Extension {$extension}",
                'passed' => extension_loaded($extension),
                'detail' => extension_loaded($extension) ? 'Available' : 'Missing',
            ];
        }

        foreach (['storage', 'storage/cache', 'storage/logs', 'storage/sessions'] as $directory) {
            $path = $rootPath . '/' . $directory;
            $checks[] = [
                'name' => "Writable {$directory}",
                'passed' => is_dir($path) && is_writable($path),
                'detail' => is_writable($path) ? 'Writable' : 'Not writable',
            ];
        }

        $checks[] = [
            'name' => 'Writable project configuration',
            'passed' => is_writable($rootPath) || (is_file($rootPath . '/.env') && is_writable($rootPath . '/.env')),
            'detail' => 'Required to create .env during installation',
        ];

        return $checks;
    }

    /** @param list<array{name: string, passed: bool, detail: string}> $checks */
    public function allPassed(array $checks): bool
    {
        foreach ($checks as $check) {
            if (! $check['passed']) {
                return false;
            }
        }

        return true;
    }
}
