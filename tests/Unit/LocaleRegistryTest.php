<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use NovaNuke\Core\I18n\LocaleRegistry;use PHPUnit\Framework\TestCase;
final class LocaleRegistryTest extends TestCase
{
 private string$directory;
 protected function setUp():void{$this->directory=sys_get_temp_dir().'/novanuke-locales-'.bin2hex(random_bytes(5));mkdir($this->directory);file_put_contents($this->directory.'/en.json','{"locale.native_name":"English"}');file_put_contents($this->directory.'/es.json','{"locale.native_name":"Español"}');}
 protected function tearDown():void{foreach(glob($this->directory.'/*')?:[]as$file)unlink($file);rmdir($this->directory);}
 public function testItDiscoversSafeCatalogueNames():void{$r=new LocaleRegistry($this->directory);self::assertSame(['en'=>'English','es'=>'Español'],$r->all());self::assertTrue($r->supports('es'));self::assertSame('en',$r->fallback('../es'));}
}
