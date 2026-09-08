<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class SocialNavigationTest extends TestCase
{
 public function testNavigationHidesOptionalDestinationsUnlessTheirModulesAreActive():void{$root=dirname(__DIR__,2);$view=file_get_contents($root.'/resources/views/auth/_social-nav.twig');self::assertStringContainsString('friends_available|default(false)',$view);self::assertStringContainsString('private_messages_available|default(false)',$view);self::assertStringContainsString('notification_unread_count is defined',$view);self::assertStringContainsString('current_user',$view);}
 public function testActiveModulesPublishAvailabilityBeforeRendering():void{$root=dirname(__DIR__,2);self::assertStringContainsString("addGlobal('friends_available',true)",file_get_contents($root.'/modules/Friends/src/FriendsModule.php'));self::assertStringContainsString("addGlobal('private_messages_available',true)",file_get_contents($root.'/modules/PrivateMessages/src/PrivateMessagesModule.php'));}
}
