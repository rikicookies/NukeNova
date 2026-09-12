<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class FriendsModuleTest extends TestCase
{
 public function testFriendActionsAreModularAndProtected():void{$root=dirname(__DIR__,2);$profile=file_get_contents($root.'/app/Auth/PublicProfileController.php');$module=file_get_contents($root.'/modules/Friends/src/FriendsModule.php');$controller=file_get_contents($root.'/modules/Friends/src/PublicFriendsController.php');self::assertStringContainsString('EventName::PROFILE_ACTIONS_BUILDING',$profile);self::assertStringContainsString('EventName::PROFILE_ACTIONS_BUILDING',$module);self::assertStringContainsString("router->post('/friends/",$module);self::assertStringContainsString('csrf->validate',$controller);self::assertStringContainsString('PrivateMessageComposerInterface::class',$module);self::assertStringContainsString('composeUrlFor',$module);}
 public function testBlockingRemovesExistingFriendship():void{$repository=file_get_contents(dirname(__DIR__,2).'/modules/Friends/src/FriendRepository.php');self::assertStringContainsString('DELETE FROM friendships',$repository);self::assertStringContainsString('friend_blocks',$repository);}
}
