<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipBoundaryContractTest extends TestCase
{
    public function testInteractiveConsumersUseMembershipContractInsteadOfEntitlementService(): void
    {
        $root=dirname(__DIR__,2);
        foreach ([
            'app/Auth/AccountController.php',
            'app/Auth/PublicProfileController.php',
            'app/Admin/UsersController.php',
            'app/Core/Access/AccessAudience.php',
            'modules/Downloads/src/DownloadRepository.php',
            'modules/WebLinks/src/WebLinkRepository.php',
            'modules/Pages/src/PageRepository.php',
            'modules/Wiki/src/WikiRepository.php',
        ] as $file) {
            $source=(string)file_get_contents($root.'/'.$file);
            self::assertStringContainsString('MembershipManagerInterface', $source, $file);
            self::assertStringNotContainsString('EntitlementService::VIP', $source, $file);
        }
    }

    public function testNoBooleanVipColumnIsIntroduced(): void
    {
        $root=dirname(__DIR__,2);
        foreach (glob($root.'/database/migrations/*.php') ?: [] as $file) {
            $source=(string)file_get_contents($file);
            self::assertDoesNotMatchRegularExpression('/\b(?:is_vip|vip_active)\b/i', $source, basename($file));
        }
    }

    public function testLegacyCustomDayGrantStillFlowsThroughMembershipContract(): void
    {
        $root=dirname(__DIR__,2);
        $contract=(string)file_get_contents($root.'/app/Core/Membership/MembershipManagerInterface.php');
        $users=(string)file_get_contents($root.'/app/Admin/UsersController.php');

        self::assertStringContainsString('grantDays(', $contract);
        self::assertStringContainsString('memberships->grantDays', $users);
        self::assertStringNotContainsString('entitlements->grant', $users);
    }
}
