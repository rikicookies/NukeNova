<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminAccountNavigationConsistencyTest extends TestCase
{
    public function testUsersAndRolesUseSharedAdminNavigationComponents(): void
    {
        foreach (['resources/views/admin/users/index.twig', 'resources/views/admin/roles/index.twig'] as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('components/breadcrumbs.twig', $source, $file);
            self::assertStringContainsString("{label: 'Admin', url: '/admin'}", $source, $file);
            self::assertStringContainsString('components/page-header.twig', $source, $file);
            self::assertStringContainsString('components/admin-row-actions.twig', $source, $file);
            self::assertStringContainsString('components/admin-table-empty.twig', $source, $file);
            self::assertStringNotContainsString('Return to dashboard', $source, $file);
        }
    }

    public function testCreateAccountShortcutMatchesExistingAuthorizationRequirements(): void
    {
        $controller = $this->source('app/Admin/UsersController.php');
        $template = $this->source('resources/views/admin/users/index.twig');

        self::assertStringContainsString("'can_create_account' => \$this->canCreateAccount()", $controller);
        self::assertStringContainsString("allows(\$userId, 'users.manage')", $controller);
        self::assertStringContainsString("allows(\$userId, 'users.assign_roles')", $controller);
        self::assertStringContainsString('isSuperAdministrator($userId)', $controller);
        self::assertStringContainsString("actions: can_create_account ? [{label: 'Create account', url: '/admin/users/create'}] : []", $template);
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
