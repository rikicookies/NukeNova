<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Downloads\src\DownloadStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DownloadStorageTest extends TestCase
{
    public function testItRejectsStoredNamesThatCouldTraverseDirectories(): void
    {
        $this->expectException(RuntimeException::class);
        (new DownloadStorage(sys_get_temp_dir()))->path('../secret.zip');
    }
}
