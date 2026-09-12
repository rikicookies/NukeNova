<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipBetaSecondStabilizationTest extends TestCase
{
    public function testAdminFreeStateDoesNotExposeVipOnlyActions(): void
    {
        $root=dirname(__DIR__,2);
        $view=(string)file_get_contents($root.'/resources/views/admin/memberships/show.twig');

        self::assertStringContainsString('{% if membership_status.vip|default(false) %}', $view);
        self::assertStringNotContainsString('{% if membership_status and membership_status.active %}', $view);
        self::assertStringContainsString('Standard registered-member access.', $view);
    }

    public function testCustomExtensionFormIsInsideFiniteVipGuard(): void
    {
        $root=dirname(__DIR__,2);
        $view=(string)file_get_contents($root.'/resources/views/admin/memberships/show.twig');

        $guard=strpos($view,'membership_status.vip|default(false) and not membership_status.lifetime|default(false)');
        $custom=strpos($view,'<label>Additional days');
        $close=strpos($view,'{% endif %}',$custom);

        self::assertNotFalse($guard);
        self::assertNotFalse($custom);
        self::assertNotFalse($close);
        self::assertLessThan($custom,$guard);
        self::assertLessThan($close,$custom);
    }

    public function testExtendCannotCreateVipFromFreeOrModifyLifetime(): void
    {
        $root=dirname(__DIR__,2);
        $source=(string)file_get_contents($root.'/app/Core/Access/EntitlementService.php');

        self::assertStringContainsString('Only an active VIP membership can be extended.', $source);
        self::assertStringContainsString('Lifetime VIP does not need an expiration extension.', $source);

        $start=strpos($source,'public function extend(');
        self::assertNotFalse($start);
        $method=substr($source,$start,5000);
        self::assertStringNotContainsString('INSERT INTO user_entitlements', $method);
    }

    public function testExtendValidationIsHandledAs422ByAdminController(): void
    {
        $root=dirname(__DIR__,2);
        $source=(string)file_get_contents($root.'/app/Admin/MembershipsController.php');

        $start=strpos($source,'public function extend(');
        self::assertNotFalse($start);
        $method=substr($source,$start,2400);
        self::assertStringContainsString('catch (InvalidArgumentException $error)', $method);
        self::assertStringContainsString(',422)', $method);
    }
}
