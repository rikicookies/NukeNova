<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminAccountEditorConsistencyTest extends TestCase
{
    public function testAccountAndRoleEditorsUseSharedAdminNavigation(): void
    {
        foreach ([
            'resources/views/admin/users/create.twig' => "{label: 'Users', url: '/admin/users'}",
            'resources/views/admin/users/edit.twig' => "{label: 'Users', url: '/admin/users'}",
            'resources/views/admin/roles/edit.twig' => "{label: 'Roles', url: '/admin/roles'}",
        ] as $file => $parentCrumb) {
            $source = $this->source($file);
            self::assertStringContainsString('components/breadcrumbs.twig', $source, $file);
            self::assertStringContainsString("{label: 'Admin', url: '/admin'}", $source, $file);
            self::assertStringContainsString($parentCrumb, $source, $file);
            self::assertStringContainsString('components/page-header.twig', $source, $file);
            self::assertStringNotContainsString('Return to users', $source, $file);
            self::assertStringNotContainsString('Return to roles', $source, $file);
        }
    }

    public function testExistingSensitiveFormsRemainPostAndCsrfProtected(): void
    {
        foreach (['resources/views/admin/users/create.twig', 'resources/views/admin/users/edit.twig', 'resources/views/admin/roles/edit.twig'] as $file) {
            $source = $this->source($file);
            self::assertStringContainsString('method="post"', $source, $file);
            self::assertStringContainsString('name="_token"', $source, $file);
        }
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/' . $file);
    }
}
