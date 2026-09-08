<?php

declare(strict_types=1);

namespace Modules\Friends\src;

use NovaNuke\Auth\ProfileActionsBuilding;use NovaNuke\Core\Container\Container;use NovaNuke\Core\Http\Request;use NovaNuke\Core\Http\Response;use NovaNuke\Core\Modules\ModuleContext;use NovaNuke\Core\Modules\ModuleInterface;use NovaNuke\Core\View\ViewRenderer;

final class FriendsModule implements ModuleInterface
{
    public function register(ModuleContext$c):void{$c->container->get(ViewRenderer::class)->addNamespace('friends',$c->basePath.'/views');$c->container->bind(FriendRepository::class,static fn(Container$x)=>new FriendRepository($x->get(\PDO::class)));$c->container->bind(FriendService::class,static fn(Container$x)=>new FriendService($x->get(FriendRepository::class),$x->get(\NovaNuke\Core\Events\EventDispatcher::class)));}
    public function boot(ModuleContext$c):void
    {
        $c->events->listen('profile.statistics.building',static function(object$event)use($c):void{if($event instanceof \NovaNuke\Auth\ProfileStatisticsBuilding)$event->add('Friends',$c->container->get(FriendRepository::class)->acceptedCount($event->profileId));});
        $c->events->listen('profile.actions.building',static function(object$event)use($c):void{if(!$event instanceof ProfileActionsBuilding)return;$state=$c->container->get(FriendRepository::class)->state($event->viewerId,$event->profileId);if($state['status']==='none')$event->add('Add friend','/friends/request/'.$event->profileId);elseif($state['status']==='pending'&&(int)$state['requested_by']!==$event->viewerId){$event->add('Accept friend','/friends/accept/'.$event->profileId);$event->add('Decline','/friends/decline/'.$event->profileId);}elseif($state['status']==='accepted'){$event->add('Remove friend','/friends/remove/'.$event->profileId);if($c->container->has(\Modules\PrivateMessages\src\PrivateMessageService::class))$event->add('Send message','/messages/compose?to='.rawurlencode($event->profileUsername),'get');}elseif($state['status']==='blocked')$event->add('Unblock','/friends/unblock/'.$event->profileId);if($state['status']!=='blocked'&&$state['status']!=='unavailable')$event->add('Block','/friends/block/'.$event->profileId);});
        $f=static fn(Container$x)=>new PublicFriendsController($x->get(FriendRepository::class),$x->get(FriendService::class),$x->get(\NovaNuke\Auth\AuthManager::class),$x->get(\NovaNuke\Core\Security\CsrfTokenManager::class),$x->get(\NovaNuke\Core\Security\SessionManager::class),$x->get(ViewRenderer::class));
        $c->router->get('/friends',static fn(Request$r,Container$x):Response=>$f($x)->index());foreach(['request','accept','decline','remove','block','unblock']as$action)$c->router->post('/friends/'.$action.'/{id}',static fn(Request$r,Container$x):Response=>$f($x)->{$action}($r));
    }
}
