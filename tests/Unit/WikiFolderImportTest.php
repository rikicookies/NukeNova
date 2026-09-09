<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiFolderImport;
use Modules\Wiki\src\WikiInput;
use Modules\Wiki\src\WikiMarkdownFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WikiFolderImportTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) if (is_file($file)) unlink($file);
    }

    public function testItConvertsFolderPathsToDraftNamespaces(): void
    {
        $first = $this->markdown('# Start');
        $second = $this->markdown('# User Roles');
        $batch = (new WikiFolderImport(new WikiMarkdownFile(), new WikiInput()))->drafts([
            'name' => ['start.md', 'roles.md'],
            'tmp_name' => [$first, $second],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($first), filesize($second)],
        ], ['docs/start.md', 'docs/admin/user-roles.md'], 2);

        self::assertSame(['docs:start', 'docs:admin:user-roles'], array_column($batch['drafts'], 'path'));
        self::assertSame(['Start', 'User Roles'], array_column($batch['drafts'], 'title'));
        self::assertSame(0, $batch['ignored']);
    }

    public function testItRejectsTraversalPaths(): void
    {
        $import = new WikiFolderImport(new WikiMarkdownFile(), new WikiInput());
        $this->expectException(RuntimeException::class);
        $import->wikiPath('../private/secret.md');
    }

    public function testItDetectsWhenPhpTruncatesTheSelectedFolder(): void
    {
        $file = $this->markdown('# Only received file');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('max_file_uploads');

        (new WikiFolderImport(new WikiMarkdownFile(), new WikiInput()))->drafts([
            'name' => ['start.md'],
            'tmp_name' => [$file],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($file)],
        ], ['docs/start.md'], 2);
    }

    private function markdown(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wiki-folder-');
        file_put_contents($path, $content);
        $this->files[] = $path;
        return $path;
    }
}
