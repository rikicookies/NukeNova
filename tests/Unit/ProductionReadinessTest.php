<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use NovaNuke\Core\Config\ConfigRepository;use NovaNuke\Core\System\ProductionReadiness;use PHPUnit\Framework\TestCase;
final class ProductionReadinessTest extends TestCase
{
 private string$root;
 protected function setUp():void{$this->root=sys_get_temp_dir().'/novanuke-production-'.bin2hex(random_bytes(5));foreach(['storage/cache','storage/logs','storage/sessions','storage/private','public/uploads']as$d)mkdir($this->root.'/'.$d,0700,true);}
 protected function tearDown():void{foreach(['storage/cache','storage/logs','storage/sessions','storage/private','public/uploads']as$d)rmdir($this->root.'/'.$d);rmdir($this->root.'/storage');rmdir($this->root.'/public');rmdir($this->root);}
 public function testItReportsUnsafeDeploymentConfiguration():void{$config=new ConfigRepository(['app'=>['environment'=>'development','debug'=>true,'url'=>'http://example.test','key'=>'short'],'session'=>['secure'=>false],'security'=>['headers_enabled'=>false],'mail'=>['mailer'=>'log']]);$checks=(new ProductionReadiness($config,$this->root))->run();$byName=[];foreach($checks as$c)$byName[$c['name']]=$c;foreach(['Production environment','Debug disabled','HTTPS URL','Secure session cookie','Security headers','Application key']as$name)self::assertFalse($byName[$name]['passed'],$name);self::assertFalse((new ProductionReadiness($config,$this->root))->passed());}
}
