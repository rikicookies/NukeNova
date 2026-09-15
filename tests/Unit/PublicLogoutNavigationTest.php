<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\I18n\Translator;
use NovaNuke\Core\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class PublicLogoutNavigationTest extends TestCase
{
    /** @dataProvider themes */
    public function testGuestDoesNotSeeLogoutAndEveryAuthenticatedAccountDoes(string $theme,string $template): void
    {
        $root=dirname(__DIR__,2);
        $views=new ViewRenderer($root.'/resources/views',$root.'/storage/cache/twig',true,new Translator('en','en',$root.'/language'));
        $views->prependPath($root.'/themes/'.$theme);
        $views->addGlobal('theme',['asset_base'=>'/assets/themes/'.$theme,'settings'=>['site_tagline'=>'Test']]);
        $base=['menus'=>['primary'=>[]],'csrf_token'=>str_repeat('a',64),'notification_unread_count'=>0];

        $guest=$views->render($template,$base+['current_user'=>null]);
        self::assertStringNotContainsString('action="/logout"',$guest);

        foreach(['member','vip','admin'] as $role){
            $html=$views->render($template,$base+['current_user'=>['id'=>1,'username'=>$role]]);
            self::assertStringContainsString('method="post"',$html,$role);
            self::assertStringContainsString('action="/logout"',$html,$role);
            self::assertStringContainsString('name="_token" value="'.str_repeat('a',64).'"',$html,$role);
        }
    }

    /** @return iterable<string,array{string,string}> */
    public static function themes(): iterable
    {
        yield 'default'=>['default','partials/header.twig'];
        yield 'classic'=>['classic','partials/header.twig'];
        yield 'novamodern'=>['novamodern','partials/public-header.twig'];
    }
}
