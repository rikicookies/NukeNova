<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WikiRevisionTest extends TestCase
{
    public function testEverySaveCreatesARevisionInsideTheTransaction(): void
    {
        $repository = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Wiki/src/WikiRepository.php');

        self::assertStringContainsString('beginTransaction()', $repository);
        self::assertStringContainsString('FOR UPDATE', $repository);
        self::assertStringContainsString('insertRevision($pageId', $repository);
        self::assertStringContainsString('MAX(revision_number)', $repository);
        self::assertStringContainsString('wiki_page_revisions', $repository);
        self::assertStringContainsString('comments_enabled', $repository);
        self::assertStringContainsString('rollBack()', $repository);

        $migration = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Wiki/database/migrations/2026_09_08_000002_create_wiki_revisions.php');
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS wiki_page_revisions', $migration);
        self::assertStringContainsString('SELECT id,1,namespace,slug,title,content', $migration);

        $commentsMigration = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Wiki/database/migrations/2026_09_09_000003_add_wiki_comments.php');
        self::assertStringContainsString("MigrationSchema::addColumn(\$database,'wiki_pages','comments_enabled'", $commentsMigration);
        self::assertStringContainsString("MigrationSchema::addColumn(\$database,'wiki_page_revisions','comments_enabled'", $commentsMigration);
    }

    public function testRestoreIsConfirmedAuthorizedAndCreatesANewRevision(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');

        self::assertStringContainsString("confirm_restore') !== '1'", $controller);
        self::assertStringContainsString("'wiki.publish'", $controller);
        self::assertStringContainsString("'wiki.page.restored'", $controller);
        self::assertStringContainsString("post('/admin/wiki/{id}/revisions/{revision}/restore'", $module);
        self::assertStringContainsString('Revision restored as a new current revision.', $controller);
    }

    public function testRevisionPreviewUsesTheSafeMarkdownPipeline(): void
    {
        $controller = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Wiki/src/AdminWikiController.php');
        self::assertStringContainsString('ContentFormat::Markdown', $controller);
        self::assertStringContainsString('ContentProfile::FullContent', $controller);
        self::assertStringContainsString("'can_restore'", $controller);
    }

    public function testRevisionComparisonIsAuthorizedAndScopedToThePage(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $template = (string) file_get_contents($root . '/modules/Wiki/views/admin/compare.twig');

        self::assertStringContainsString("guard('wiki.edit')", $controller);
        self::assertStringContainsString('revision($pageId, $fromId)', $controller);
        self::assertStringContainsString('revision($pageId, $toId)', $controller);
        self::assertStringContainsString("get('/admin/wiki/{id}/compare'", $module);
        self::assertStringNotContainsString('|raw', $template);
    }
}
