<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BundledModuleVisualConsistencyTest extends TestCase
{
    public function testEveryBundledModuleWasIncludedInTheBeta28VisualAudit(): void
    {
        $root=dirname(__DIR__,2);
        $expected=[
            'Comments','DemoContent','Downloads','Friends','Media','News','Notifications','Pages',
            'Polls','PrivateMessages','Quotes','Search','Seo','Statistics','WebLinks','Welcome','Wiki',
        ];

        $actual=[];
        foreach(new \DirectoryIterator($root.'/modules') as $item){
            if($item->isDot()||!$item->isDir()) continue;
            $actual[]=$item->getFilename();
        }
        sort($actual);
        sort($expected);
        foreach($expected as $module){
            self::assertContains($module,$actual,"Bundled module {$module} is missing from the distribution.");
        }

        $audit=(string)file_get_contents($root.'/docs/VISUAL_AUDIT_BETA28.md');
        foreach($expected as $module){
            self::assertStringContainsString('| '.$module.' |',$audit,"{$module} is missing from the visual audit.");
        }
    }

    public function testLegacyVisualSurfacesUseSharedPageAndEmptyStatePrimitives(): void
    {
        $root=dirname(__DIR__,2);
        $expectations=[
            'modules/Polls/views/index.twig'=>['components/page-header.twig','components/empty-state.twig','poll-list'],
            'modules/Statistics/views/index.twig'=>['components/page-header.twig','components/empty-state.twig','stat-grid'],
            'modules/Friends/views/index.twig'=>['components/page-header.twig','components/empty-state.twig','user-grid'],
            'modules/Notifications/views/index.twig'=>['components/page-header.twig','components/empty-state.twig','notification-list'],
            'modules/PrivateMessages/views/inbox.twig'=>['components/page-header.twig','components/empty-state.twig','message-list'],
            'modules/Search/views/index.twig'=>['components/page-header.twig','components/empty-state.twig','search-results'],
            'modules/Media/views/admin/index.twig'=>['components/page-header.twig','components/empty-state.twig','media-grid'],
            'modules/Quotes/views/index.twig'=>['components/page-header.twig','components/empty-state.twig','content-list'],
        ];

        foreach($expectations as $relative=>$needles){
            $source=(string)file_get_contents($root.'/'.$relative);
            foreach($needles as $needle){
                self::assertStringContainsString($needle,$source,"{$relative} is missing {$needle}.");
            }
        }
    }

    public function testSharedModulePrimitivesHaveLightThemeOverrides(): void
    {
        $root=dirname(__DIR__,2);
        $app=(string)file_get_contents($root.'/public/assets/css/app.css');
        $modern=(string)file_get_contents($root.'/themes/novamodern/assets/css/novamodern.css');
        $classic=(string)file_get_contents($root.'/themes/classic/assets/css/classic.css');

        foreach(['.module-section','.empty-state','.surface-card','.module-metric-grid','.module-split'] as $selector){
            self::assertStringContainsString($selector,$app);
        }
        self::assertStringContainsString('Beta 28 bundled-module polish',$modern);
        self::assertStringContainsString('Beta 28 bundled-module polish',$classic);
    }
}
