<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiInput;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WikiInputTest extends TestCase
{
    public function testItValidatesWikiSearchTerms(): void
    {
        $input = new WikiInput();

        self::assertSame('pool installation', $input->searchTerm('  pool installation  '));
        $this->expectException(RuntimeException::class);
        $input->searchTerm('x');
    }

    public function testItRejectsNonScalarWikiSearchTerms(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiInput())->searchTerm(['private']);
    }

    public function testItSplitsAValidatedNamespacePath(): void
    {
        $page = (new WikiInput())->page([
            'path' => 'Guides:Getting-Started',
            'title' => 'Getting started',
            'content' => '# Welcome',
            'status' => 'published',
            'audience' => 'vip',
            'comments_enabled' => '1',
        ], true);

        self::assertSame('guides', $page['namespace']);
        self::assertSame('getting-started', $page['slug']);
        self::assertSame('guides:getting-started', $page['path']);
        self::assertSame('vip', $page['audience']);
        self::assertSame(1, $page['comments_enabled']);
    }

    public function testItRejectsTraversalAndSlashPaths(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiInput())->path('../private/page');
    }

    public function testEditorWithoutPublishPermissionCanOnlySaveDrafts(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiInput())->page([
            'path' => 'private:notes',
            'title' => 'Notes',
            'content' => 'Text',
            'status' => 'published',
            'audience' => 'public',
        ], false);
    }

    public function testItValidatesDirectoryNamespacesIndependently(): void
    {
        self::assertSame('guides:server-admin', (new WikiInput())->namespace('Guides:Server-Admin'));

        $this->expectException(RuntimeException::class);
        (new WikiInput())->namespace('guides/../../private');
    }

    public function testItRejectsNamespacesLongerThanTheDatabaseColumn(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiInput())->path(str_repeat('a', 95) . ':' . str_repeat('b', 95) . ':page');
    }

    public function testItRejectsArrayQueryInputWithoutCastingWarnings(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiInput())->namespace(['private']);
    }
}
