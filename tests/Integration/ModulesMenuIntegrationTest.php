<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Access\AccessAudience;
use NovaNuke\Core\Access\EntitlementService;
use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Routing\Router;
use NovaNuke\Core\I18n\Translator;
use NovaNuke\Core\Membership\MembershipService;
use NovaNuke\Core\Modules\ModuleCompatibilityChecker;
use NovaNuke\Core\Modules\ModuleDetector;
use NovaNuke\Core\Modules\ModuleManager;
use NovaNuke\Core\Modules\ModuleMigrator;
use NovaNuke\Core\Modules\ModuleRepository;
use NovaNuke\Core\Modules\ModulesMenuBuilder;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\Version;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;

final class ModulesMenuIntegrationTest extends MySqlIntegrationTestCase
{
    public function testEnabledAudienceAwareModulesAppearInStableOrderAndStateChangesImmediately(): void
    {
        $root=dirname(__DIR__,2);$events=new EventDispatcher();
        $_SESSION=[];$session=new SessionManager('novanuke_modules_menu_'.bin2hex(random_bytes(4)),false);$session->start();
        $auth=new AuthManager($this->db(),$session,$events);
        $manager=new ModuleManager($this->db(),new ModuleDetector($root.'/modules'),new ModuleRepository($this->db()),new ModuleMigrator($this->db()),new ModuleCompatibilityChecker(Version::CURRENT),new Container(),new Router(),$events,new Translator('en','en',$root.'/language'));
        foreach(['friends','media','news','pages','polls'] as$slug){$manager->install($slug);$manager->enable($slug);}
        $builder=new ModulesMenuBuilder($manager,$auth,new AccessAudience(new MembershipService(new EntitlementService($this->db()))),new Translator('en','en',$root.'/language'));

        self::assertSame(['news','pages','polls'],array_column($builder->items(),'slug'));
        $manager->disable('news');self::assertSame(['pages','polls'],array_column($builder->items(),'slug'));
        $manager->enable('news');self::assertSame(['news','pages','polls'],array_column($builder->items(),'slug'));

        $password='Modules-Menu-93!';
        $this->db()->prepare("INSERT INTO users(username,email,password_hash,status,email_verified_at,created_at,updated_at) VALUES('menu-member','menu-member@example.test',:hash,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())")->execute(['hash'=>password_hash($password,PASSWORD_DEFAULT)]);
        self::assertNotNull($auth->attempt('menu-member',$password,'127.0.0.1','PHPUnit'));
        self::assertSame(['news','pages','polls','friends'],array_column($builder->items(),'slug'));

        $this->db()->exec("INSERT INTO modules(slug,name,installed_version,enabled,manifest,installed_at,updated_at) VALUES('missing-module','Missing','1.0.0',1,'{}',UTC_TIMESTAMP(),UTC_TIMESTAMP())");
        self::assertSame(['news','pages','polls','friends'],array_column($builder->items(),'slug'));
    }
}
