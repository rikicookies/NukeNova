<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WikiModuleContractTest extends TestCase
{
    public function testWikiIsIndependentAndUsesProtectedAdministrativeWrites(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/modules/Wiki/module.json'), true, 32, JSON_THROW_ON_ERROR);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $admin = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');

        self::assertSame([], $manifest['dependencies']);
        self::assertSame(['wiki.edit', 'wiki.publish'], $manifest['permissions']);
        self::assertStringContainsString("post('/admin/wiki/save'", $module);
        self::assertStringContainsString('csrf->validate', $admin);
        self::assertStringContainsString("guard('wiki.edit')", $admin);
        self::assertStringContainsString('ContentFormat::Markdown', $public);
        self::assertStringContainsString("'vip'", (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php'));
    }

    public function testMissingPageCreationIsPermissionAware(): void
    {
        $public = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Wiki/src/PublicWikiController.php');
        self::assertStringContainsString("allows((int) \$user['id'], 'wiki.edit')", $public);
        self::assertStringContainsString("rawurlencode(\$path)", $public);
        self::assertStringContainsString("byPath(\$path)", $public);
        self::assertStringContainsString("), 404)", $public);
    }

    public function testBacklinksAndMissingLinkReportUseTheSharedLinkIndexer(): void
    {
        $root = dirname(__DIR__, 2);
        $repository = (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');
        $admin = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');

        self::assertStringContainsString('WikiLinkIndexer $links', $repository);
        self::assertStringContainsString('! $this->canView($page, $userId)', $repository);
        self::assertStringContainsString("'backlinks' => \$this->pages->backlinks", $public);
        self::assertStringContainsString("'missing_links' => \$this->pages->missingLinks()", $admin);
    }

    public function testMarkdownImportAndExportAreAuthorizedAndImportUsesCsrf(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $controller = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');

        self::assertStringContainsString("post('/admin/wiki/import'", $module);
        self::assertStringContainsString("get('/admin/wiki/{id}/export'", $module);
        self::assertStringContainsString("guard('wiki.edit')", $controller);
        self::assertStringContainsString("csrf->validate", $controller);
        self::assertStringContainsString("'text/markdown; charset=UTF-8'", $controller);
        self::assertStringContainsString("'X-Content-Type-Options' => 'nosniff'", $controller);
    }

    public function testFullMarkdownArchiveExportIsAuthorizedAndUsesTemporaryPrivateDelivery(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $controller = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $repository = (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php');

        self::assertStringContainsString("get('/admin/wiki/export-all'", $module);
        self::assertStringContainsString("guard('wiki.edit')", $controller);
        self::assertStringContainsString('archive->create($this->pages->exportPages())', $controller);
        self::assertStringContainsString("'Content-Type' => 'application/zip'", $controller);
        self::assertStringContainsString("'Cache-Control' => 'private, no-store'", $controller);
        self::assertStringContainsString('if (is_file($path)) unlink($path)', $controller);
        self::assertStringContainsString('WHERE deleted_at IS NULL', $repository);
    }

    public function testFolderImportRequiresConfirmationAndCreatesOnlyNewDrafts(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $controller = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $view = (string) file_get_contents($root . '/modules/Wiki/views/admin/index.twig');

        self::assertStringContainsString("post('/admin/wiki/import-folder'", $module);
        self::assertStringContainsString("guard('wiki.edit')", $controller);
        self::assertStringContainsString('csrf->validate', $controller);
        self::assertStringContainsString("input('confirm_import') !== '1'", $controller);
        self::assertStringContainsString('existingPaths()', $controller);
        self::assertStringContainsString("'status' => 'draft'", (string) file_get_contents($root . '/modules/Wiki/src/WikiMarkdownFile.php'));
        self::assertStringContainsString('webkitdirectory directory multiple', $view);
        self::assertStringContainsString('/assets/js/wiki-folder-import.js', $view);
    }

    public function testBulkActionsAreTransactionalAuthorizedAndExplicitlyConfirmed(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $controller = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $repository = (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php');
        $view = (string) file_get_contents($root . '/modules/Wiki/views/admin/index.twig');

        self::assertStringContainsString("post('/admin/wiki/bulk'", $module);
        self::assertStringContainsString("guard('wiki.edit')", $controller);
        self::assertStringContainsString("guard('wiki.publish')", $controller);
        self::assertStringContainsString("input('confirm_bulk') !== '1'", $controller);
        self::assertStringContainsString("input('page_ids', [])", $controller);
        self::assertStringContainsString('count($value) > 500', $controller);
        self::assertStringContainsString('function bulkChange(array $ids', $repository);
        self::assertStringContainsString('beginTransaction()', $repository);
        self::assertStringContainsString('insertRevision($id', $repository);
        self::assertStringContainsString('rollBack()', $repository);
        self::assertStringContainsString('name="page_ids[]"', $view);
        self::assertStringContainsString('data-wiki-select-all', $view);
        self::assertStringContainsString('name="confirm_bulk" value="1" required', $view);
    }

    public function testMarkdownPreviewIsAuthorizedSanitizedAndNeverPersisted(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $controller = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $preview = substr(
            $controller,
            strpos($controller, 'public function preview'),
            strpos($controller, 'public function import') - strpos($controller, 'public function preview'),
        );

        self::assertStringContainsString("post('/admin/wiki/preview'", $module);
        self::assertStringContainsString('function preview(Request $request)', $controller);
        self::assertStringContainsString("guard('wiki.edit')", $controller);
        self::assertStringContainsString("['status' => 'draft']", $controller);
        self::assertStringContainsString('ContentFormat::Markdown', $controller);
        self::assertStringContainsString('ContentProfile::FullContent', $controller);
        self::assertStringContainsString("'Cache-Control' => 'private, no-store'", $controller);
        self::assertStringNotContainsString('pages->save', $preview);
        self::assertStringNotContainsString('activity->log', $preview);
    }

    public function testCommentsRemainOptionalAndTargetChecksEnforcePageVisibility(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $repository = (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');

        self::assertStringContainsString("listen('comments.content.checking'", $module);
        self::assertStringContainsString("event->type !== 'wiki'", $module);
        self::assertStringContainsString('has(CommentService::class)', $module);
        self::assertStringContainsString('comments_enabled=1', $repository);
        self::assertStringContainsString('&& $this->canView($page, $userId)', $repository);
        self::assertStringContainsString("comments->for('wiki'", $public);
    }

    public function testPublicDirectoryUsesValidatedHierarchicalNamespaceNavigation(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');

        self::assertStringContainsString('WikiNavigation::class', $module);
        self::assertStringContainsString('->index($request)', $module);
        self::assertStringContainsString("input->namespace(\$request->query('namespace'", $public);
        self::assertStringContainsString("pages->directory(\$user ? (int) \$user['id'] : null)", $public);
        self::assertStringContainsString('WikiLinkPresenter::class', $module);
        self::assertStringContainsString('linkPresenter->markMissing', $public);
    }

    public function testBrowsableWikiMapUsesTheExistingPermissionFilteredDirectory(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');
        $view = (string) file_get_contents($root . '/modules/Wiki/views/map.twig');

        self::assertLessThan(strpos($module, "get('/wiki/{path}'"), strpos($module, "get('/wiki/map'"));
        self::assertStringContainsString('navigation->sitemap($this->pages->directory', $public);
        self::assertStringContainsString('<details open>', $view);
        self::assertStringContainsString('_self.namespace_tree(namespace.namespaces)', $view);
    }

    public function testRecentChangesOnlyUseVisiblePublishedPagesAndPrecedeTheCatchAllRoute(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $repository = (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');
        $view = (string) file_get_contents($root . '/modules/Wiki/views/recent.twig');

        self::assertLessThan(strpos($module, "get('/wiki/{path}'"), strpos($module, "get('/wiki/recent'"));
        self::assertStringContainsString('function recentChanges(?int $userId', $repository);
        self::assertStringContainsString("status='published'", $repository);
        self::assertStringContainsString('array_filter($pages, fn (array $page): bool => $this->canView($page, $userId))', $repository);
        self::assertStringContainsString('pages->recentChanges', $public);
        self::assertStringContainsString("trans('wiki::recent.title')", $view);
    }

    public function testDedicatedSearchIsPreparedPermissionAwareAndPrecedesTheCatchAllRoute(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $repository = (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');

        self::assertLessThan(strpos($module, "get('/wiki/{path}'"), strpos($module, "get('/wiki/search'"));
        self::assertStringContainsString('database->prepare', $repository);
        self::assertStringContainsString("title LIKE :title ESCAPE '='", $repository);
        self::assertStringContainsString("content LIKE :content ESCAPE '='", $repository);
        self::assertStringContainsString('canView($page, $userId)', $repository);
        self::assertStringContainsString('input->searchTerm', $public);
        self::assertStringContainsString('pages->search', $public);
    }

    public function testPublicWikiPagesContributeToTheExtensibleXmlSitemap(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/modules/Wiki/module.json'), true, 32, JSON_THROW_ON_ERROR);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $repository = (string) file_get_contents($root . '/modules/Wiki/src/WikiRepository.php');

        self::assertContains('sitemap.collecting', $manifest['events']);
        self::assertStringContainsString('EventName::SITEMAP_COLLECTING', $module);
        self::assertStringContainsString("event->add('/wiki'", $module);
        self::assertStringContainsString("event->add('/wiki/' . \$path", $module);
        self::assertStringContainsString("audience='public'", $repository);
        self::assertStringContainsString("status='published'", $repository);
        self::assertStringContainsString('published_at<=UTC_TIMESTAMP()', $repository);
        self::assertStringContainsString('deleted_at IS NULL', $repository);
    }

    public function testAttachmentsUsePrivateStorageAndProtectedAdministrativeWrites(): void
    {
        $root = dirname(__DIR__, 2);
        $module = (string) file_get_contents($root . '/modules/Wiki/src/WikiModule.php');
        $admin = (string) file_get_contents($root . '/modules/Wiki/src/AdminWikiController.php');
        $public = (string) file_get_contents($root . '/modules/Wiki/src/PublicWikiController.php');
        $migration = (string) file_get_contents($root . '/modules/Wiki/database/migrations/2026_09_09_000004_create_wiki_attachments.php');

        self::assertStringContainsString("NOVANUKE_ROOT . '/storage/private/wiki'", $module);
        self::assertLessThan(
            strpos($module, "get('/wiki/{path}'"),
            strpos($module, "get('/wiki/attachments/{attachment}'"),
        );
        self::assertStringContainsString("post('/admin/wiki/{id}/attachments'", $module);
        self::assertStringContainsString('csrf->validate', $admin);
        self::assertStringContainsString("guard('wiki.edit')", $admin);
        self::assertStringContainsString('Response::download', $public);
        self::assertStringContainsString('pages->canView', $public);
        self::assertStringContainsString("query('inline') === '1'", $public);
        self::assertStringContainsString("['image/png', 'image/jpeg', 'image/webp']", $public);
        self::assertStringContainsString('ON DELETE CASCADE', $migration);
    }
}
