<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class ModuleAudienceTest extends TestCase
{
 public function testModuleRoutesCarryOwnersAndAreGuardedServerSide():void
 {
  $root=dirname(__DIR__,2);$router=file_get_contents($root.'/app/Core/Http/Routing/Router.php');$manager=file_get_contents($root.'/app/Core/Modules/ModuleManager.php');$kernel=file_get_contents($root.'/app/Core/Http/Kernel.php');$menus=file_get_contents($root.'/app/Core/Menus/MenuManager.php');
  self::assertStringContainsString('beginOwner',$router);self::assertStringContainsString('endOwner',$manager);self::assertStringContainsString('moduleAccess->guard',$kernel);self::assertStringContainsString("link_type'] !== 'module'",$menus);
 }
 public function testAudienceIsPersistedAndConfiguredWithCsrf():void
 {
  $root=dirname(__DIR__,2);$repository=file_get_contents($root.'/app/Core/Modules/ModuleRepository.php');$controller=file_get_contents($root.'/app/Admin/ModulesController.php');$view=file_get_contents($root.'/resources/views/admin/modules/index.twig');
  self::assertStringContainsString('setAudience',$repository);self::assertStringContainsString('csrf->validate',$controller);self::assertStringContainsString('module.audience.updated',$controller);self::assertStringContainsString('Active VIP only',$view);
 }
}
