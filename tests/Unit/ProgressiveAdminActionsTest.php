<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProgressiveAdminActionsTest extends TestCase
{
    public function testMembershipMutationsOptInAndKeepPostCsrfFallback(): void
    {
        $root = dirname(__DIR__, 2);
        $view = (string) file_get_contents($root . '/resources/views/admin/memberships/show.twig');
        $routes = (string) file_get_contents($root . '/routes/admin.php');
        $controller = (string) file_get_contents($root . '/app/Admin/MembershipsController.php');

        self::assertStringContainsString('id="membership-detail"', $view);
        foreach (['/assign', '/extend', '/revoke', '/schedule/cancel'] as $action) {
            self::assertStringContainsString($action . '"', $view);
        }
        self::assertGreaterThanOrEqual(5, substr_count($view, 'data-ajax-replace="#membership-detail"'));
        self::assertGreaterThanOrEqual(6, substr_count($view, 'method="post"'));
        self::assertGreaterThanOrEqual(6, substr_count($view, 'name="_token"'));

        foreach (['assign', 'extend', 'revoke'] as $action) {
            self::assertStringContainsString("'/admin/memberships/{id}/{$action}'", $routes);
        }
        self::assertStringContainsString("'/admin/memberships/{id}/schedule/cancel'", $routes);
        self::assertStringContainsString("'memberships.manage'", $controller);
        self::assertGreaterThanOrEqual(5, substr_count($controller, '$this->csrf->validate'));
    }

    public function testLargeOrSensitiveAdminFormsRemainTraditional(): void
    {
        $root = dirname(__DIR__, 2);
        $blocks = (string) file_get_contents($root . '/resources/views/admin/blocks/index.twig');
        $users = (string) file_get_contents($root . '/resources/views/admin/users/edit.twig');
        $menus = (string) file_get_contents($root . '/resources/views/admin/menus/index.twig');

        self::assertStringNotContainsString('data-ajax-action', $blocks);
        self::assertStringNotContainsString('data-ajax-action', $users);
        self::assertStringNotContainsString('data-ajax-action', $menus);
    }

    public function testSharedHelperRejectsMissingFragmentsAndDuplicateSubmits(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/progressive-actions.js');

        self::assertStringContainsString("form.dataset.ajaxPending === 'true'", $script);
        self::assertStringContainsString("form.dataset.ajaxPending = 'true'", $script);
        self::assertStringContainsString('delete form.dataset.ajaxPending;', $script);
        self::assertStringContainsString("'Action failed because the page could not be refreshed.'", $script);
        self::assertStringNotContainsString(
            "announce(feedback?.message || (response.ok ? 'Action completed.' : 'Action failed.')",
            $script,
        );
    }
}
