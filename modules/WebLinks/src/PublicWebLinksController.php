<?php

declare(strict_types=1);

namespace Modules\WebLinks\src;

use NovaNuke\Auth\AuthManager;use NovaNuke\Core\Http\Request;use NovaNuke\Core\Http\Response;use NovaNuke\Core\Security\CsrfTokenManager;use NovaNuke\Core\Security\SessionManager;use NovaNuke\Core\View\ViewRenderer;use RuntimeException;use Twig\Markup;

final class PublicWebLinksController
{
    public function __construct(private readonly WebLinkRepository $links,private readonly WebLinkInput $input,private readonly WebLinkManager $manager,private readonly AuthManager $auth,private readonly CsrfTokenManager $csrf,private readonly SessionManager $session,private readonly ViewRenderer $views){}
    public function index(Request $r,?string $category=null):Response{if($category!==null&&!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$category))return Response::html('Category not found.',404);$page=filter_var($r->query('page',1),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:1;$q=trim((string)$r->query('q',''));if(mb_strlen($q)>100)$q=mb_substr($q,0,100);$order=(string)$r->query('order','new');if(!in_array($order,['new','popular','title'],true))$order='new';return Response::html($this->views->render('@web-links/index.twig',['result'=>$this->links->catalog((int)$page,$category,$q,$order),'categories'=>$this->links->categories(),'category'=>$category,'search'=>$q,'order'=>$order]));}
    public function show(Request $r):Response{$link=$this->links->publicBySlug($this->slug($r->attribute('slug')));if($link===null)return Response::html('Link not found.',404);$link['description_html']=new Markup((string)$link['description'],'UTF-8');return Response::html($this->views->render('@web-links/show.twig',['link'=>$link,'csrf_token'=>$this->csrf->token(),'message'=>$this->session->pull('web-links.message'),'error'=>$this->session->pull('web-links.error')]));}
    public function submitForm():Response{if($this->auth->user()===null)return Response::redirect('/login');return$this->submission();}
    public function submit(Request $r):Response{if($this->auth->user()===null)return Response::redirect('/login');if(!$this->csrf->validate($r->input('_token')))return Response::html('Invalid or expired CSRF token.',419);try{$this->manager->submit($this->input->link($r->allInput(),false));$this->session->put('web-links.message','Link submitted for review.');return Response::redirect('/links',303);}catch(RuntimeException $e){return$this->submission($r->allInput(),$e->getMessage(),422);}}
    public function visit(Request $r):Response{try{return Response::externalRedirect($this->manager->visit($this->slug($r->attribute('slug')),$r),302);}catch(RuntimeException|\InvalidArgumentException){return Response::html('Link not found.',404);}}
    public function report(Request $r):Response{if(!$this->csrf->validate($r->input('_token')))return Response::html('Invalid or expired CSRF token.',419);$return='/links';try{$slug=$this->slug($r->input('slug'));$id=$this->id($r->attribute('id'));$this->manager->report($id,$r);$this->session->put('web-links.message','Broken-link report submitted.');$return='/links/'.$slug;}catch(RuntimeException $e){$this->session->put('web-links.error',$e->getMessage());}return Response::redirect($return,303);}
    private function submission(array $link=[],?string $error=null,int $status=200):Response{return Response::html($this->views->render('@web-links/submit.twig',['link'=>$link,'categories'=>$this->links->categories(),'csrf_token'=>$this->csrf->token(),'error'=>$error]),$status);}
    private function slug(mixed $v):string{$v=(string)$v;if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$v)||mb_strlen($v)>200)throw new RuntimeException('Invalid link.');return$v;}
    private function id(mixed $v):int{$id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new RuntimeException('Invalid link.');return(int)$id;}
}
