<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\System\MaintenanceAccessPolicy;
use PHPUnit\Framework\TestCase;

final class MaintenanceAccessPolicyTest extends TestCase
{
    public function testItBlocksPublicContentOnlyWhenInstalledAndEnabled(): void
    {
        $policy = new MaintenanceAccessPolicy();
        self::assertFalse($policy->blocks('/news', false, true, false));
        self::assertFalse($policy->blocks('/news', true, false, false));
        self::assertTrue($policy->blocks('/news', true, true, false));
    }

    public function testItPreservesRecoveryAdministrationAndHealthRoutes(): void
    {
        $policy = new MaintenanceAccessPolicy();
        foreach (['/login', '/logout', '/forgot-password', '/reset-password/token', '/admin', '/admin/system', '/health'] as $path) {
            self::assertFalse($policy->blocks($path, true, true, false), $path);
        }
    }

    public function testSuperAdministratorCanPreviewThePublicSite(): void
    {
        self::assertFalse((new MaintenanceAccessPolicy())->blocks('/news', true, true, true));
    }
}
