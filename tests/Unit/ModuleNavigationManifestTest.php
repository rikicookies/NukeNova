<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Modules\ModuleDetector;
use PHPUnit\Framework\TestCase;

final class ModuleNavigationManifestTest extends TestCase
{
    public function testBundledPublicNavigationMetadataIsValidAndStablyOrdered(): void
    {
        $manifests=(new ModuleDetector(dirname(__DIR__,2).'/modules'))->detect();
        $items=[];
        foreach($manifests as$slug=>$manifest){
            if($manifest->navigation!==null)$items[]=['slug'=>$slug]+$manifest->navigation;
        }
        usort($items,static fn(array$a,array$b):int=>[$a['order'],$a['label'],$a['slug']]<=>[$b['order'],$b['label'],$b['slug']]);
        self::assertSame(
            ['news','pages','downloads','web-links','search','wiki','polls','statistics','quotes','welcome','notifications','private-messages','friends'],
            array_column($items,'slug'),
        );
        self::assertNotContains('media',array_column($items,'slug'));
        self::assertNotContains('comments',array_column($items,'slug'));
        self::assertNotContains('seo',array_column($items,'slug'));
        self::assertNotContains('demo-content',array_column($items,'slug'));
    }
}
