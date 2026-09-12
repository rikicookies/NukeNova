<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class ProfileStatisticsTest extends TestCase
{
 public function testProfileStatisticsAreProvidedOnlyByActiveModules():void{$root=dirname(__DIR__,2);$controller=file_get_contents($root.'/app/Auth/PublicProfileController.php');self::assertStringContainsString('EventName::PROFILE_STATISTICS_BUILDING',$controller);foreach(['News','Comments','Friends']as$module){$source=file_get_contents($root.'/modules/'.$module.'/src/'.$module.'Module.php');self::assertStringContainsString('EventName::PROFILE_STATISTICS_BUILDING',$source);}}
 public function testPublicTemplateRendersCollectedStatistics():void{$view=file_get_contents(dirname(__DIR__,2).'/resources/views/auth/profile-public.twig');self::assertStringContainsString('profile_statistics',$view);self::assertStringContainsString('statistic.value',$view);}
}
