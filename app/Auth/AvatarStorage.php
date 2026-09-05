<?php

declare(strict_types=1);

namespace NovaNuke\Auth;

use RuntimeException;

final class AvatarStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    public function store(ValidatedAvatar $avatar): string
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0750, true) && ! is_dir($this->directory)) {
            throw new RuntimeException('Avatar storage could not be created.');
        }
        if (! is_writable($this->directory) || ! is_uploaded_file($avatar->temporaryPath)) throw new RuntimeException('Avatar storage is unavailable.');
        $filename = bin2hex(random_bytes(20)) . '.' . $avatar->extension;
        if (! move_uploaded_file($avatar->temporaryPath, $this->directory . '/' . $filename)) throw new RuntimeException('Avatar could not be stored.');
        return '/avatars/' . $filename;
    }

    /** @return array{path:string,mime:string,size:int} */
    public function resolve(string $filename): array
    {
        if (! preg_match('/^[a-f0-9]{40}\.(?:jpg|png|webp)$/', $filename)) throw new RuntimeException('Avatar not found.');
        $root = realpath($this->directory); $path = realpath($this->directory . '/' . $filename);
        if ($root === false || $path === false || ! str_starts_with($path, $root . DIRECTORY_SEPARATOR) || ! is_file($path)) throw new RuntimeException('Avatar not found.');
        $mimes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        return ['path' => $path, 'mime' => $mimes[pathinfo($filename, PATHINFO_EXTENSION)], 'size' => (int) filesize($path)];
    }

    public function remove(?string $publicPath): void
    {
        if ($publicPath === null || ! preg_match('#^/avatars/([a-f0-9]{40}\.(?:jpg|png|webp))$#', $publicPath, $match)) return;
        try { $avatar = $this->resolve($match[1]); } catch (RuntimeException) { return; }
        if (! unlink($avatar['path'])) throw new RuntimeException('Previous avatar could not be removed.');
    }
}
