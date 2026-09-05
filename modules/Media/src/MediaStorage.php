<?php

declare(strict_types=1);

namespace Modules\Media\src;

use RuntimeException;

final class MediaStorage
{
    public function __construct(private readonly string $publicRoot)
    {
    }

    public function store(ValidatedMedia $media): string
    {
        $relative='uploads/media/'.gmdate('Y').'/'.gmdate('m');$directory=$this->publicRoot.'/'.$relative;
        if (!is_dir($directory) && !mkdir($directory,0750,true) && !is_dir($directory)) throw new RuntimeException('Media directory could not be created.');
        if (!is_writable($directory) || !is_uploaded_file($media->temporaryPath)) throw new RuntimeException('Media storage is unavailable.');
        $filename=bin2hex(random_bytes(20)).'.'.$media->extension;$path=$directory.'/'.$filename;
        if (!move_uploaded_file($media->temporaryPath,$path)) throw new RuntimeException('Image could not be stored.');
        chmod($path,0640);return '/'.$relative.'/'.$filename;
    }

    public function remove(string $publicPath): void
    {
        if (!preg_match('#^/uploads/media/\d{4}/\d{2}/[a-f0-9]{40}\.(?:jpg|png|webp)$#',$publicPath)) throw new RuntimeException('Stored media path is invalid.');
        $root=realpath($this->publicRoot.'/uploads/media');$path=realpath($this->publicRoot.$publicPath);
        if ($path===false) return;
        if ($root===false || !str_starts_with($path,$root.DIRECTORY_SEPARATOR) || !is_file($path) || is_link($path)) throw new RuntimeException('Stored media path escaped the media directory.');
        if (!unlink($path)) throw new RuntimeException('Image file could not be removed.');
    }
}
