<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class FriendsModuleTest extends TestCase
{
 public function testFriendActionsAreModularAndProtected():void{$root=dirname(__DIR__,2);$profile=file_get_contents($root.'/app/Auth/PublicProfileController.php');$module=file_get_contents($root.'/modules/Friends/src/FriendsModule.php');$controller=file_get_contents($root.'/modules/Friends/src/PublicFriendsController.php');self::assertStringContainsString("dispatch('profile.actions.building'",$profile);self::assertStringContainsString("listen('profile.actions.building'",$module);self::assertStringContainsString("router->post('/friends/",$module);self::assertStringContainsString('csrf->validate',$controller);self::assertStringContainsString('PrivateMessageService::class',$module);self::assertStringContainsString("'/messages/compose?to='",$module);}
 public function testBlockingRemovesExistingFriendship():void{$repository=file_get_contents(dirname(__DIR__,2).'/modules/Friends/src/FriendRepository.php');self::assertStringContainsString('DELETE FROM friendships',$repository);self::assertStringContainsString('friend_blocks',$repository);}
}
