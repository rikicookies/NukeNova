<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Statistics\src\StatisticsDimensions;use PHPUnit\Framework\TestCase;

final class StatisticsDimensionsTest extends TestCase
{
    public function testItClassifiesWithoutRetainingPathsOrFullUserAgents():void{$d=(new StatisticsDimensions())->classify('/news/private-slug?secret=1','https://www.google.com/search?q=private','Mozilla/5.0 (iPhone) AppleWebKit Safari/605.1','novanuke.test');self::assertSame('news',$d['section']);self::assertSame('google.com',$d['referrer']);self::assertSame('safari',$d['browser']);self::assertSame('mobile',$d['device']);self::assertArrayNotHasKey('path',$d);}
    public function testItTreatsInternalAndIpReferrersAsPrivateBuckets():void{$classifier=new StatisticsDimensions();self::assertSame('direct',$classifier->classify('/','https://novanuke.test/news','Chrome','novanuke.test')['referrer']);self::assertSame('other',$classifier->classify('/unknown','http://192.168.1.20/private','Firefox','novanuke.test')['referrer']);self::assertSame('other',$classifier->classify('/unknown','','Firefox','novanuke.test')['section']);}
}
