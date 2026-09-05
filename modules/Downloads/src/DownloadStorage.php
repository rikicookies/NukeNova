<?php

declare(strict_types=1);

namespace Modules\Downloads\src;

use RuntimeException;

final class DownloadStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    /** @return array{stored_name:string,original_name:string,file_size:int,mime_type:string} */
    public function store(ValidatedUpload $upload): array
    {
        if (! is_dir($this->directory) || ! is_writable($this->directory)) throw new RuntimeException('Private download storage is not writable.');
        if (! is_uploaded_file($upload->temporaryPath)) throw new RuntimeException('Upload source was not accepted by PHP.');
        $stored = bin2hex(random_bytes(20)) . '.' . $upload->extension;
        if (! move_uploaded_file($upload->temporaryPath, $this->directory . '/' . $stored)) throw new RuntimeException('The uploaded file could not be stored.');
        return ['stored_name' => $stored, 'original_name' => $upload->originalName, 'file_size' => $upload->size, 'mime_type' => $upload->mimeType];
    }

    public function path(string $storedName): string
    {
        if (! preg_match('/^[a-f0-9]{40}\.[a-z0-9]{2,5}$/', $storedName)) throw new RuntimeException('Stored download filename is invalid.');
        $root = realpath($this->directory);
        $path = realpath($this->directory . '/' . $storedName);
        if ($root === false || $path === false || ! str_starts_with($path, $root . DIRECTORY_SEPARATOR) || ! is_file($path)) {
            throw new RuntimeException('Download file is unavailable.');
        }
        return $path;
    }

    public function remove(string $storedName): void
    {
        try { $path = $this->path($storedName); } catch (RuntimeException) { return; }
        if (! unlink($path)) throw new RuntimeException('Stored upload could not be cleaned up.');
    }
}
