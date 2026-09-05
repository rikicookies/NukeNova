<?php

declare(strict_types=1);

namespace Modules\WebLinks\src;

use NovaNuke\Auth\AuthManager;use NovaNuke\Core\Http\Request;use NovaNuke\Core\Http\Response;use NovaNuke\Core\Logging\ActivityLogger;use NovaNuke\Core\Security\AuthorizationService;use NovaNuke\Core\Security\CsrfTokenManager;use NovaNuke\Core\Security\SessionManager;use NovaNuke\Core\View\ViewRenderer;use RuntimeException;

final class AdminWebLinksController
{
    public function __construct(private readonly WebLinkRepository $links,private readonly WebLinkInput $input,private readonly AuthManager $auth,private readonly AuthorizationService $authorization,private readonly ActivityLogger $activity,private readonly CsrfTokenManager $csrf,private readonly SessionManager $session,private readonly ViewRenderer $views){}
    public function index():Response{if($g=$this->guard())return$g;return Response::html($this->views->render('@web-links/admin/index.twig',['links'=>$this->links->adminLinks(),'categories'=>$this->links->categories(),'reports'=>$this->links->reports(),'csrf_token'=>$this->csrf->token(),'message'=>$this->session->pull('web-links.admin.message'),'error'=>$this->session->pull('web-links.admin.error')]));}
    public function create():Response{if($g=$this->guard())return$g;return$this->editor([]);}
    public function edit(Request $r):Response{if($g=$this->guard())return$g;try{$link=$this->links->find($this->id($r->attribute('id')));return$link===null?Response::html('Link not found.',404):$this->editor($link);}catch(RuntimeException){return Response::html('Link not found.',404);}}
    public function save(Request $r):Response{if($g=$this->guard())return$g;if($c=$this->csrf($r))return$c;try{$actor=$this->auth->user();$id=filter_var($r->input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;$link=$this->links->save($id?(int)$id:null,$this->input->link($r->allInput(),true),(int)$actor['id']);$this->activity->log((int)$actor['id'],'web-link.saved','web_link',$link,[],$r->ip());return$this->redirect('Link saved.');}catch(RuntimeException $e){return$this->editor($r->allInput(),$e->getMessage(),422);}}
    public function category(Request $r):Response{if($g=$this->guard())return$g;if($c=$this->csrf($r))return$c;try{$this->links->saveCategory($this->input->category($r->allInput()));return$this->redirect('Category created.');}catch(RuntimeException $e){return$this->redirect(null,$e->getMessage());}}
    public function delete(Request $r):Response{if($g=$this->guard())return$g;if($c=$this->csrf($r))return$c;try{if($r->input('confirm_delete')!=='1')throw new RuntimeException('Confirm deletion.');$id=$this->id($r->attribute('id'));$this->links->delete($id);$actor=$this->auth->user();$this->activity->log((int)$actor['id'],'web-link.deleted','web_link',$id,[],$r->ip());return$this->redirect('Link deleted.');}catch(RuntimeException $e){return$this->redirect(null,$e->getMessage());}}
    public function resolve(Request $r):Response{if($g=$this->guard())return$g;if($c=$this->csrf($r))return$c;try{$id=$this->id($r->attribute('id'));$this->links->resolve($id);$actor=$this->auth->user();$this->activity->log((int)$actor['id'],'web-link.report.resolved','web_link_report',$id,[],$r->ip());return$this->redirect('Report resolved.');}catch(RuntimeException $e){return$this->redirect(null,$e->getMessage());}}
    private function editor(array $link,?string $error=null,int $status=200):Response{return Response::html($this->views->render('@web-links/admin/edit.twig',['link'=>$link,'categories'=>$this->links->categories(),'csrf_token'=>$this->csrf->token(),'error'=>$error]),$status);}
    private function guard():?Response{$u=$this->auth->user();if($u===null)return Response::redirect('/login');return$this->authorization->allows((int)$u['id'],'web-links.manage')?null:Response::html('Forbidden',403);}
    private function csrf(Request $r):?Response{return$this->csrf->validate($r->input('_token'))?null:Response::html('Invalid or expired CSRF token.',419);}
    private function id(mixed $v):int{$id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new RuntimeException('Invalid link.');return(int)$id;}
    private function redirect(?string $message=null,?string $error=null):Response{if($message)$this->session->put('web-links.admin.message',$message);if($error)$this->session->put('web-links.admin.error',$error);return Response::redirect('/admin/web-links',303);}
}
