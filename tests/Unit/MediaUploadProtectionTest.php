<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class MediaUploadProtectionTest extends TestCase
{
 public function testPublicUploadsDisableExecutableHandlers():void{$rules=(string)file_get_contents(dirname(__DIR__,2).'/public/uploads/.htaccess');self::assertStringContainsString('Options -Indexes -ExecCGI',$rules);self::assertStringContainsString('RemoveHandler',$rules);self::assertMatchesRegularExpression('/php/i',$rules);}
}
