<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use NovaNuke\Core\Content\ContentChanged;use NovaNuke\Core\Database\Migration;use NovaNuke\Core\Events\EventDispatcher;use NovaNuke\Core\Events\EventName;use NovaNuke\Core\Profile\ProfileActionsBuilding;use NovaNuke\Core\Profile\ProfileStatisticsBuilding;use NovaNuke\Core\ModuleApi;use NovaNuke\Core\Search\SearchProviderInterface;use NovaNuke\Core\Search\SearchProviderRegistryInterface;use NovaNuke\Core\Sitemap\SitemapCollecting;use NovaNuke\Core\Modules\ModuleContext;use NovaNuke\Core\Modules\ModuleInterface;use PHPUnit\Framework\TestCase;use ReflectionClass;
final class PublicModuleApiTest extends TestCase
{
 public function testVersionOneContractsKeepTheirPublicShape():void
 {
  self::assertSame('1.0',ModuleApi::VERSION);self::assertSame(['boot','register'],$this->methods(ModuleInterface::class));self::assertSame(['down','up'],$this->methods(Migration::class));self::assertSame(['dispatch','listen','listenerCount'],$this->methods(EventDispatcher::class));$properties=array_map(fn($p)=>$p->getName(),(new ReflectionClass(ModuleContext::class))->getProperties());sort($properties);self::assertSame(['basePath','container','events','manifest','router'],$properties);self::assertTrue(interface_exists(SearchProviderInterface::class));self::assertTrue(interface_exists(SearchProviderRegistryInterface::class));self::assertTrue(class_exists(SitemapCollecting::class));self::assertTrue(class_exists(EventName::class));self::assertTrue(class_exists(ContentChanged::class));self::assertTrue(class_exists(ProfileActionsBuilding::class));self::assertTrue(class_exists(ProfileStatisticsBuilding::class));
 }
 /** @param class-string $class @return list<string> */ private function methods(string$class):array{$methods=array_map(fn($m)=>$m->getName(),(new ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC));sort($methods);return$methods;}
}
