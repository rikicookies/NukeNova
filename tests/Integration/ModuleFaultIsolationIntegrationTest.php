<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Container\Container;use NovaNuke\Core\Events\EventDispatcher;use NovaNuke\Core\Http\Request;use NovaNuke\Core\Http\Response;use NovaNuke\Core\Http\Routing\RouteNotFound;use NovaNuke\Core\Http\Routing\Router;use NovaNuke\Core\I18n\Translator;use NovaNuke\Core\Modules\ModuleCompatibilityChecker;use NovaNuke\Core\Modules\ModuleContext;use NovaNuke\Core\Modules\ModuleDetector;use NovaNuke\Core\Modules\ModuleInterface;use NovaNuke\Core\Modules\ModuleManager;use NovaNuke\Core\Modules\ModuleMigrator;use NovaNuke\Core\Modules\ModuleRepository;use NovaNuke\Core\Version;use NovaNuke\Core\View\ViewRenderer;use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;use PDO;

final class ModuleFaultIsolationIntegrationTest extends MySqlIntegrationTestCase
{
 private?string$modules=null;
 public function testFailedModulesLeaveNoPartialRuntimeStateAndHealthyModuleStillBoots():void
 {
  $this->modules=sys_get_temp_dir().'/novanuke-module-isolation-'.bin2hex(random_bytes(8));mkdir($this->modules,0700,true);
  $fixtures=['BootFailure'=>BootFailureModule::class,'Healthy'=>HealthyModule::class,'RegisterFailure'=>RegisterFailureModule::class];
  foreach($fixtures as$directory=>$class){$provider="Modules\\{$directory}\\src\\{$directory}Module";if(!class_exists($provider,false))class_alias($class,$provider);$this->fixture($directory,$provider);}
  $root=dirname(__DIR__,2);$container=new Container();$router=new Router();$events=new EventDispatcher();$translator=new Translator('en','en',$root.'/language');$views=new ViewRenderer($root.'/resources/views',$root.'/storage/cache/twig-tests',true,$translator);$container->instance(PDO::class,$this->db());$container->instance(ViewRenderer::class,$views);
  $manager=new ModuleManager($this->db(),new ModuleDetector($this->modules),new ModuleRepository($this->db()),new ModuleMigrator($this->db()),new ModuleCompatibilityChecker(Version::CURRENT),$container,$router,$events,$translator);
  foreach(['boot-failure','healthy','register-failure']as$slug){$manager->install($slug);$manager->enable($slug);}$manager->bootEnabled();
  self::assertSame('/healthy',$router->match(Request::create('GET','/healthy'))->route->path);self::assertSame('healthy',$container->get('fixture.service'));self::assertSame(1,$events->listenerCount('fixture.event'));self::assertSame('healthy',$translator->translate('healthy::message'));self::assertSame('healthy',trim($views->render('@healthy/index.twig')));
  self::assertFalse($container->has('register-failure.service'));self::assertFalse($container->has('boot-failure.service'));
  $inventory=$manager->inventory();self::assertSame('register failed intentionally',$inventory['register-failure']['last_error']);self::assertSame('boot failed intentionally',$inventory['boot-failure']['last_error']);
  foreach(['/register-failure','/boot-failure']as$path){try{$router->match(Request::create('GET',$path));self::fail("Leaked route: {$path}");}catch(RouteNotFound){self::assertTrue(true);}}
  self::assertSame('boot-failure::message',$translator->translate('boot-failure::message'));
 }
 protected function tearDown():void{if($this->modules!==null&&is_dir($this->modules)){foreach(glob($this->modules.'/*/*/*')?:[]as$f)if(is_file($f))unlink($f);foreach(glob($this->modules.'/*/*')?:[]as$d)if(is_dir($d))rmdir($d);foreach(glob($this->modules.'/*/module.json')?:[]as$f)if(is_file($f))unlink($f);foreach(glob($this->modules.'/*')?:[]as$d)if(is_dir($d))rmdir($d);if(is_dir($this->modules))rmdir($this->modules);}parent::tearDown();}
 private function fixture(string$directory,string$provider):void{$path=$this->modules.'/'.$directory;mkdir($path.'/language',0700,true);mkdir($path.'/views',0700,true);$slug=strtolower((string)preg_replace('/(?<!^)[A-Z]/','-$0',$directory));file_put_contents($path.'/language/en.json','{"message":"'.$slug.'"}');file_put_contents($path.'/views/index.twig',$slug);file_put_contents($path.'/module.json',json_encode(['name'=>$directory,'slug'=>$slug,'version'=>'1.0.0','provider'=>$provider,'cms_min_version'=>'0.1.0','php_min_version'=>'8.3.0','dependencies'=>[],'permissions'=>[],'events'=>[],'api_version'=>'1.0'],JSON_THROW_ON_ERROR));}
}
final class HealthyModule implements ModuleInterface{public function register(ModuleContext$c):void{$c->container->bind('fixture.service',static fn():string=>'healthy');$c->container->get(ViewRenderer::class)->addNamespace('healthy',$c->basePath.'/views');}public function boot(ModuleContext$c):void{$c->events->listen('fixture.event',static function(object$e):void{});$c->router->get('/healthy',static fn():Response=>Response::html('healthy'));}}
final class RegisterFailureModule implements ModuleInterface{public function register(ModuleContext$c):void{$c->container->bind('register-failure.service',static fn():string=>'leak');$c->events->listen('fixture.event',static function(object$e):void{});$c->router->get('/register-failure',static fn():Response=>Response::html('leak'));throw new \RuntimeException('register failed intentionally');}public function boot(ModuleContext$c):void{}}
final class BootFailureModule implements ModuleInterface{public function register(ModuleContext$c):void{$c->container->bind('fixture.service',static fn():string=>'leak');$c->container->bind('boot-failure.service',static fn():string=>'leak');$c->container->get(ViewRenderer::class)->addNamespace('boot-failure',$c->basePath.'/views');}public function boot(ModuleContext$c):void{$c->events->listen('fixture.event',static function(object$e):void{});$c->router->get('/boot-failure',static fn():Response=>Response::html('leak'));throw new \RuntimeException('boot failed intentionally');}}
