<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiMarkdownFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WikiMarkdownFileTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'wiki-md-');
        file_put_contents($this->file, "\xEF\xBB\xBF# Getting Started\r\n\r\nSafe **Markdown**.");
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) unlink($this->file);
    }

    public function testItImportsValidatedUtf8MarkdownAsAnUnsavedDraft(): void
    {
        $draft = (new WikiMarkdownFile())->import([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $this->file,
            'name' => '../getting-started.md',
            'size' => filesize($this->file),
        ]);

        self::assertSame('getting-started', $draft['path']);
        self::assertSame('Getting Started', $draft['title']);
        self::assertSame('draft', $draft['status']);
        self::assertStringStartsWith('# Getting Started', $draft['content']);
    }

    public function testItRejectsAFileWithoutTheMdExtension(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiMarkdownFile())->import([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $this->file,
            'name' => 'payload.php',
            'size' => filesize($this->file),
        ]);
    }

    public function testItGeneratesAPathBasedExportFilename(): void
    {
        $files = new WikiMarkdownFile();
        self::assertSame('guides-installation.md', $files->filename('guides:installation'));
        self::assertSame('unsafe-header.md', $files->filename("unsafe\r\nHeader"));
    }
}
