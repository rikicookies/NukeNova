<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use InvalidArgumentException;use Modules\Media\src\MediaUsageChecking;use PHPUnit\Framework\TestCase;
final class MediaUsageCheckingTest extends TestCase
{
 public function testItAggregatesUsageBySource():void{$e=new MediaUsageChecking('/uploads/media/2026/09/a.png');$e->add('news.featured-image',2);$e->add('news.featured-image',1);$e->add('pages.image',4);self::assertSame(7,$e->total());self::assertSame(['news.featured-image'=>3,'pages.image'=>4],$e->uses());}
 public function testItRejectsInvalidSources():void{$this->expectException(InvalidArgumentException::class);(new MediaUsageChecking('/x'))->add('../bad',1);}
}
