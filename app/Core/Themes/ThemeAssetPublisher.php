<?php

declare(strict_types=1);

namespace NovaNuke\Core\Themes;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class ThemeAssetPublisher
{
    private const ALLOWED_EXTENSIONS = [
        'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'map',
    ];

    public function __construct(private readonly string $publicThemePath)
    {
    }

    public function publish(ThemeManifest $manifest): void
    {
        $destination = $this->destination($manifest->slug);
        $this->removeDirectory($destination);
        if (! mkdir($destination, 0775, true) && ! is_dir($destination)) {
            throw new RuntimeException('Could not create the public theme asset directory.');
        }

        $source = $manifest->path . '/assets';
        if (is_dir($source)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
            );
            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    throw new RuntimeException('Theme assets may not contain symbolic links.');
                }
                $relative = substr($item->getPathname(), strlen($source) + 1);
                $target = $destination . '/' . str_replace('\\', '/', $relative);
                if ($item->isDir()) {
                    if (! is_dir($target) && ! mkdir($target, 0775, true)) {
                        throw new RuntimeException('Could not create a theme asset directory.');
                    }
                    continue;
                }
                $extension = strtolower($item->getExtension());
                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new RuntimeException("Theme asset extension is not allowed: {$extension}");
                }
                if (! copy($item->getPathname(), $target)) {
                    throw new RuntimeException('Could not publish a theme asset.');
                }
            }
        }

        if ($manifest->screenshot !== '') {
            $screenshot = basename($manifest->screenshot);
            $sourceScreenshot = $manifest->path . '/' . $screenshot;
            $extension = strtolower(pathinfo($screenshot, PATHINFO_EXTENSION));
            if (! is_file($sourceScreenshot) || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                throw new RuntimeException('Theme screenshot is missing or unsupported.');
            }
            if (! copy($sourceScreenshot, $destination . '/' . $screenshot)) {
                throw new RuntimeException('Could not publish the theme screenshot.');
            }
        }
    }

    public function remove(string $slug): void
    {
        $this->removeDirectory($this->destination($slug));
    }

    private function destination(string $slug): string
    {
        if (! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $slug)) {
            throw new RuntimeException('Invalid theme asset destination.');
        }

        return rtrim($this->publicThemePath, '/') . '/' . $slug;
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $root = rtrim(str_replace('\\', '/', $this->publicThemePath), '/');
        $normalized = str_replace('\\', '/', $path);
        if (! str_starts_with($normalized . '/', $root . '/') || $normalized === $root) {
            throw new RuntimeException('Refusing to remove an unsafe theme asset path.');
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
