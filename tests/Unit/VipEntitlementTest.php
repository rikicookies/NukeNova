<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use DateTimeImmutable;
use NovaNuke\Core\Access\EntitlementService;
use PHPUnit\Framework\TestCase;
final class VipEntitlementTest extends TestCase
{
    public function testVipUsesExpiringEntitlementsAndProtectedAdminActions():void
    {
        $root=dirname(__DIR__,2);$service=file_get_contents($root.'/app/Core/Access/EntitlementService.php');$controller=file_get_contents($root.'/app/Admin/UsersController.php');$migration=file_get_contents($root.'/database/migrations/2026_09_08_000015_create_user_entitlements.php');
        self::assertStringContainsString("public const VIP='vip'",$service);self::assertStringContainsString('expires_at>UTC_TIMESTAMP()',$service);self::assertStringContainsString('FOR UPDATE',$service);self::assertStringContainsString('user_entitlements',$migration);self::assertStringContainsString('creationGuard()',$controller);self::assertStringContainsString('csrf->validate',$controller);self::assertStringContainsString('user.vip.granted',$controller);self::assertStringContainsString('user.vip.revoked',$controller);
    }

    public function testEntitlementIsActiveOnlyInsideItsUnrevokedPeriod():void
    {
        $now=new DateTimeImmutable('2026-09-08 12:00:00 UTC');
        self::assertTrue(EntitlementService::recordIsActive(['starts_at'=>'2026-09-01 00:00:00','expires_at'=>'2026-09-09 00:00:00','revoked_at'=>null],$now));
        self::assertFalse(EntitlementService::recordIsActive(['starts_at'=>'2026-09-01 00:00:00','expires_at'=>'2026-09-08 12:00:00','revoked_at'=>null],$now));
        self::assertFalse(EntitlementService::recordIsActive(['starts_at'=>'2026-09-01 00:00:00','expires_at'=>'2026-09-09 00:00:00','revoked_at'=>'2026-09-08 11:00:00'],$now));
    }

    public function testVipStatusAndAudienceLabelsAreExposedWithoutWeakeningAuthorization():void
    {
        $root=dirname(__DIR__,2);$account=file_get_contents($root.'/app/Auth/AccountController.php');$public=file_get_contents($root.'/app/Auth/PublicProfileController.php');
        self::assertStringContainsString('MembershipManagerInterface',$account);self::assertStringContainsString("'membership'",$public);
        foreach(['News','Pages','Downloads','WebLinks'] as $module){$view=file_get_contents($root.'/modules/'.$module.'/views/admin/index.twig');self::assertStringContainsString('Active VIP members',$view);}
        foreach(['News','Pages','Downloads','WebLinks'] as $module){$controller=file_get_contents($root.'/modules/'.$module.'/src/Public'.$module.'Controller.php');self::assertStringNotContainsString("Response::html('Forbidden'",$controller);}
    }
}
