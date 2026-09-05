<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Integration;
use NovaNuke\Core\Menus\MenuRepository;use NovaNuke\Core\Modules\ModuleRepository;use NovaNuke\Core\Settings\SettingsRepository;use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
final class RepositoryPerformanceIntegrationTest extends MySqlIntegrationTestCase
{
 public function testSettingsRequestCacheIsInvalidatedAfterWrites():void{$r=new SettingsRepository($this->db());self::assertSame('NovaNuke',$r->string('site.name','NovaNuke'));self::assertSame('NovaNuke',$r->string('site.name','NovaNuke'));$r->setString('site.name','Fast NovaNuke','site');self::assertSame('Fast NovaNuke',$r->string('site.name'));}
 public function testModuleInventoryCacheIsInvalidatedAfterStateChanges():void{$this->db()->exec("INSERT INTO modules(slug,name,installed_version,enabled,manifest,installed_at,updated_at)VALUES('probe','Probe','1.0.0',0,'{}',UTC_TIMESTAMP(),UTC_TIMESTAMP())");$r=new ModuleRepository($this->db());self::assertFalse($r->all()['probe']['enabled']);$r->setEnabled('probe',true);self::assertTrue($r->all()['probe']['enabled']);}
 public function testEnabledMenusAreHydratedInBatches():void{$menus=(new MenuRepository($this->db()))->enabledWithItems();self::assertNotEmpty($menus);self::assertArrayHasKey('items',$menus[0]);self::assertNotEmpty($menus[0]['items']);self::assertArrayHasKey('role_slugs',$menus[0]['items'][0]);}
}
