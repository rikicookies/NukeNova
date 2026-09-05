<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use NovaNuke\Core\I18n\CatalogueAuditor;use PHPUnit\Framework\TestCase;
final class CatalogueAuditorTest extends TestCase
{
 private string$root;
 protected function setUp():void{$this->root=sys_get_temp_dir().'/novanuke-catalogues-'.bin2hex(random_bytes(5));mkdir($this->root.'/language',0700,true);}
 protected function tearDown():void{foreach(glob($this->root.'/language/*')?:[]as$file)unlink($file);rmdir($this->root.'/language');rmdir($this->root);}
 public function testItAcceptsMatchingCatalogues():void{file_put_contents($this->root.'/language/en.json','{"hello":"Hello"}');file_put_contents($this->root.'/language/es.json','{"hello":"Hola"}');self::assertTrue((new CatalogueAuditor($this->root))->run()[0]['passed']);}
 public function testItReportsMissingKeys():void{file_put_contents($this->root.'/language/en.json','{"hello":"Hello"}');file_put_contents($this->root.'/language/es.json','{}');self::assertFalse((new CatalogueAuditor($this->root))->run()[0]['passed']);}
 public function testBundledCataloguesHaveMatchingEnglishAndSpanishKeys():void{foreach((new CatalogueAuditor(dirname(__DIR__,2)))->run()as$check)self::assertTrue($check['passed'],$check['label'].': '.$check['detail']);}
}
