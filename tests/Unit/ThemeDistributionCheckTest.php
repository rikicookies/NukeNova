<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Themes\ThemeDistributionCheck;
use PHPUnit\Framework\TestCase;

final class ThemeDistributionCheckTest extends TestCase
{
    public function testBundledThemesPassDistributionCheck(): void
    {
        $root=dirname(__DIR__,2);
        $check=new ThemeDistributionCheck($root.'/themes');

        foreach($check->run() as $item){
            self::assertTrue($item['passed'],$item['name'].': '.$item['detail']);
        }
    }
}
