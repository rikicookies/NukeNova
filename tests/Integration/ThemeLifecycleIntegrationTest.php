<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\I18n\Translator;
use NovaNuke\Core\Settings\SettingsRepository;
use NovaNuke\Core\Themes\ThemeActivated;
use NovaNuke\Core\Themes\ThemeAssetPublisher;
use NovaNuke\Core\Themes\ThemeDetector;
use NovaNuke\Core\Themes\ThemeManager;
use NovaNuke\Core\Themes\ThemeRepository;
use NovaNuke\Core\Version;
use NovaNuke\Core\View\ViewRenderer;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;

final class ThemeLifecycleIntegrationTest extends MySqlIntegrationTestCase
{
    private ?string $work=null;

    protected function tearDown(): void
    {
        parent::tearDown();
        if($this->work!==null&&is_dir($this->work)){
            $iterator=new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->work,\FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach($iterator as $item){
                $item->isDir()?@rmdir($item->getPathname()):@unlink($item->getPathname());
            }
            @rmdir($this->work);
        }
        $this->work=null;
    }

    public function testBundledThemesInstallActivateAndSwitchWithoutServerStateCorruption(): void
    {
        $root=dirname(__DIR__,2);
        $this->work=sys_get_temp_dir().'/novanuke-theme-integration-'.bin2hex(random_bytes(6));
        mkdir($this->work.'/assets',0770,true);
        mkdir($this->work.'/cache',0770,true);

        $settings=new SettingsRepository($this->db());
        $events=new EventDispatcher();
        $activated=[];
        $events->listen(\NovaNuke\Core\Events\EventName::THEME_ACTIVATED,static function(object $event) use (&$activated): void {
            if($event instanceof ThemeActivated) $activated[]=$event->slug;
        });

        $translator=new Translator('en','en',$root.'/language');
        $manager=new ThemeManager(
            new ThemeDetector($root.'/themes'),
            new ThemeRepository($this->db()),
            new ThemeAssetPublisher($this->work.'/assets'),
            $settings,
            new ViewRenderer($root.'/resources/views',$this->work.'/cache',true,$translator),
            $events,
            $translator,
            Version::CURRENT,
        );

        foreach(['default','classic','novamodern'] as $slug){
            $manager->install($slug);
            self::assertTrue($manager->inventory()[$slug]['installed']);
        }

        $manager->activate('default');
        self::assertSame('default',$manager->activeSlug());
        $manager->activate('novamodern');
        self::assertSame('novamodern',$manager->activeSlug());
        $manager->activate('classic');
        self::assertSame('classic',$manager->activeSlug());

        self::assertSame(['default','novamodern','classic'],$activated);

        foreach(['default','classic','novamodern'] as $slug){
            self::assertDirectoryExists($this->work.'/assets/'.$slug);
        }

        try{
            $manager->uninstall('classic');
            self::fail('Active theme must not be uninstallable.');
        }catch(\RuntimeException $error){
            self::assertStringContainsString('Activate another theme',$error->getMessage());
        }

        $manager->activate('default');
        $manager->uninstall('classic');
        self::assertFalse($manager->inventory()['classic']['installed']);
        self::assertSame('default',$manager->activeSlug());
    }
}
