<?php

declare(strict_types=1);

namespace Modules\Media\src;

use NovaNuke\Auth\AuthManager;use NovaNuke\Core\Http\Request;use NovaNuke\Core\Http\Response;use NovaNuke\Core\Logging\ActivityLogger;use NovaNuke\Core\Security\AuthorizationService;use NovaNuke\Core\Security\CsrfTokenManager;use NovaNuke\Core\Security\SessionManager;use NovaNuke\Core\View\ViewRenderer;use RuntimeException;

final class AdminMediaController
{
    public function __construct(private readonly MediaRepository $repository,private readonly MediaManager $manager,private readonly AuthManager $auth,private readonly AuthorizationService $authorization,private readonly ActivityLogger $activity,private readonly CsrfTokenManager $csrf,private readonly SessionManager $session,private readonly ViewRenderer $views){}
    public function index():Response{if($g=$this->guard())return$g;return Response::html($this->views->render('@media/admin/index.twig',['images'=>$this->repository->all(),'csrf_token'=>$this->csrf->token(),'message'=>$this->session->pull('media.message'),'error'=>$this->session->pull('media.error')]));}
    public function upload(Request $r):Response{if($g=$this->guard())return$g;if(!$this->csrf->validate($r->input('_token')))return Response::html('Invalid or expired CSRF token.',419);try{$u=$this->auth->user();$id=$this->manager->upload($r->file('image'),(int)$u['id'],$r->input('title'),$r->input('alt_text'));$this->activity->log((int)$u['id'],'media.uploaded','media',$id,[],$r->ip());return$this->back('Image uploaded.');}catch(RuntimeException $e){return$this->back(null,$e->getMessage());}}
    public function update(Request $r):Response{return$this->action($r,function(int $id)use($r):void{$this->manager->update($id,$r->input('title'),$r->input('alt_text'));},'Image metadata updated.');}
    public function delete(Request $r):Response{return$this->action($r,function(int$id)use($r):void{if($r->input('confirm_delete')!=='1')throw new RuntimeException('Confirm image deletion.');$this->manager->delete($id);},'Image deleted.','media.deleted');}
    private function action(Request$r,callable$callback,string$message,string$event='media.updated'):Response{if($g=$this->guard())return$g;if(!$this->csrf->validate($r->input('_token')))return Response::html('Invalid or expired CSRF token.',419);try{$id=$this->id($r->attribute('id'));$callback($id);$u=$this->auth->user();$this->activity->log((int)$u['id'],$event,'media',$id,[],$r->ip());return$this->back($message);}catch(RuntimeException$e){return$this->back(null,$e->getMessage());}}
    private function guard():?Response{$u=$this->auth->user();if($u===null)return Response::redirect('/login');return$this->authorization->allows((int)$u['id'],'media.manage')?null:Response::html('Forbidden',403);}
    private function id(mixed$v):int{$id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new RuntimeException('Invalid media identifier.');return(int)$id;}
    private function back(?string$m=null,?string$e=null):Response{if($m!==null)$this->session->put('media.message',$m);if($e!==null)$this->session->put('media.error',$e);return Response::redirect('/admin/media',303);}
}
