<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiArchive;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

final class WikiArchiveTest extends TestCase
{
    public function testItConvertsNamespacesToSafeArchiveDirectories(): void
    {
        $archive = new WikiArchive();
        self::assertSame('guides/admin/users.md', $archive->entryPath('guides:admin', 'users'));

        $this->expectException(RuntimeException::class);
        $archive->entryPath('../private', 'secret');
    }

    public function testItCreatesAnArchiveContainingMarkdownSources(): void
    {
        if (! class_exists(ZipArchive::class)) self::markTestSkipped('The ZIP extension is not available.');
        $path = (new WikiArchive())->create([
            ['namespace' => '', 'slug' => 'start', 'content' => '# Start'],
            ['namespace' => 'guides:admin', 'slug' => 'users', 'content' => '# Users'],
        ]);
        try {
            $zip = new ZipArchive();
            self::assertTrue($zip->open($path));
            self::assertSame('# Start', $zip->getFromName('start.md'));
            self::assertSame('# Users', $zip->getFromName('guides/admin/users.md'));
            $zip->close();
        } finally {
            if (is_file($path)) unlink($path);
        }
    }
}
