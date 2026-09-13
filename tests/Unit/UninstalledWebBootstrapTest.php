<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Application;
use NovaNuke\Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class UninstalledWebBootstrapTest extends TestCase
{
    public function testInstallerPageBootsWithoutResolvingDatabaseServices(): void
    {
        $root = $this->freshRoot();

        try {
            self::assertFileDoesNotExist($root . '/.env');
            self::assertFileDoesNotExist($root . '/storage/installed.lock');

            $application = Application::create($root);
            $response = $application->kernel()->handle(Request::create('GET', '/install'));

            self::assertSame(200, $response->status());
            self::assertStringContainsString('database', strtolower($response->content()));
            self::assertStringContainsString('NovaNuke', $response->content());
        } finally {
            restore_error_handler();
            $this->removeTree($root);
        }
    }

    public function testUninstalledHomeRedirectsToInstallerWithoutDatabaseConnection(): void
    {
        $root = $this->freshRoot();

        try {
            $application = Application::create($root);
            $response = $application->kernel()->handle(Request::create('GET', '/'));

            self::assertSame(302, $response->status());
            self::assertSame('/install', $response->header('Location'));
        } finally {
            restore_error_handler();
            $this->removeTree($root);
        }
    }

    private function freshRoot(): string
    {
        $source = dirname(__DIR__, 2);
        $root = sys_get_temp_dir() . '/novanuke-uninstalled-' . bin2hex(random_bytes(6));

        if (! mkdir($root, 0770, true) && ! is_dir($root)) {
            throw new \RuntimeException('Unable to create uninstalled bootstrap fixture.');
        }

        foreach (['config', 'routes', 'resources/views', 'language'] as $directory) {
            $this->copyTree($source . '/' . $directory, $root . '/' . $directory);
        }

        return $root;
    }

    private function copyTree(string $source, string $target): void
    {
        if (! is_dir($target) && ! mkdir($target, 0770, true) && ! is_dir($target)) {
            throw new \RuntimeException("Unable to create fixture directory {$target}.");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($source) + 1);
            $destination = $target . '/' . str_replace('\\', '/', $relative);

            if ($item->isDir()) {
                if (! is_dir($destination) && ! mkdir($destination, 0770, true) && ! is_dir($destination)) {
                    throw new \RuntimeException("Unable to create fixture directory {$destination}.");
                }
                continue;
            }

            if (! copy($item->getPathname(), $destination)) {
                throw new \RuntimeException("Unable to copy fixture file {$relative}.");
            }
        }
    }

    private function removeTree(string $root): void
    {
        if (! is_dir($root)) return;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($root);
    }
}
