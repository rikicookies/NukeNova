<?php

declare(strict_types=1);

namespace Modules\PrivateMessages\src;

use NovaNuke\Auth\AuthManager;
use NovaNuke\Core\Http\Request;
use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\CsrfTokenManager;
use NovaNuke\Core\Security\SessionManager;
use NovaNuke\Core\View\ViewRenderer;
use RuntimeException;

final class PublicPrivateMessagesController
{
    public function __construct(private readonly PrivateMessageRepository $repository,private readonly PrivateMessageService $service,private readonly AuthManager $auth,private readonly CsrfTokenManager $csrf,private readonly SessionManager $session,private readonly ViewRenderer $views) {}

    public function inbox(): Response { if(!$user=$this->user())return Response::redirect('/login');return $this->view('@private-messages/inbox.twig',['conversations'=>$this->repository->inbox((int)$user['id'])]); }
    public function sent(): Response { if(!$user=$this->user())return Response::redirect('/login');return $this->view('@private-messages/sent.twig',['messages'=>$this->repository->sent((int)$user['id'])]); }
    public function compose(Request $request): Response { if(!$this->user())return Response::redirect('/login');return $this->view('@private-messages/compose.twig',['recipient'=>(string)$request->query('to','')]); }
    public function show(Request $request): Response { if(!$user=$this->user())return Response::redirect('/login');try{$id=$this->id($request->attribute('id'));}catch(RuntimeException){return Response::html('Conversation not found.',404);}$thread=$this->repository->conversation($id,(int)$user['id']);return $thread===null?Response::html('Conversation not found.',404):$this->view('@private-messages/show.twig',['thread'=>$thread]); }

    public function store(Request $request): Response
    {
        if(!$user=$this->user())return Response::redirect('/login');if(!$this->csrf->validate($request->input('_token')))return Response::html('Invalid or expired CSRF token.',419);
        try{$conversation=$this->service->compose((int)$user['id'],(string)$request->input('recipient'),$request->input('subject'),$request->input('body'));$this->session->put('private-messages.message','Message sent.');return Response::redirect('/messages/'.$conversation,303);}
        catch(RuntimeException $e){return $this->view('@private-messages/compose.twig',['recipient'=>$request->input('recipient'),'subject'=>$request->input('subject'),'body'=>$request->input('body'),'error'=>$e->getMessage()],422);}
    }

    public function reply(Request $request): Response { return $this->action($request,fn(int $user,int $id)=>$this->service->reply($id,$user,$request->input('body')),'Reply sent.'); }
    public function delete(Request $request): Response { return $this->action($request,fn(int $user,int $id)=>$this->repository->deleteFor($id,$user),'Conversation removed from your inbox.','/messages'); }
    public function report(Request $request): Response { return $this->action($request,fn(int $user,int $id)=>$this->service->report($id,$user,$request->input('reason')),'Report submitted.'); }
    public function block(Request $request): Response { return $this->action($request,fn(int $user,int $id)=>$this->repository->block($user,$id),'User blocked.','/messages/blocks'); }
    public function unblock(Request $request): Response { return $this->action($request,fn(int $user,int $id)=>$this->repository->unblock($user,$id),'User unblocked.','/messages/blocks'); }
    public function blocks(): Response { if(!$user=$this->user())return Response::redirect('/login');return $this->view('@private-messages/blocks.twig',['blocks'=>$this->repository->blocks((int)$user['id'])]); }

    private function action(Request $request,callable $callback,string $message,string $fallback=''): Response
    {
        if(!$user=$this->user())return Response::redirect('/login');if(!$this->csrf->validate($request->input('_token')))return Response::html('Invalid or expired CSRF token.',419);
        try{$id=$this->id($request->attribute('id'));$callback((int)$user['id'],$id);$this->session->put('private-messages.message',$message);}
        catch(RuntimeException $e){$this->session->put('private-messages.error',$e->getMessage());return Response::redirect($fallback!==''?$fallback:'/messages',303);}
        $conversation=filter_var($request->input('conversation_id',$id),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:$id;
        return Response::redirect($fallback!==''?$fallback:'/messages/'.$conversation,303);
    }
    private function view(string $template,array $data=[],int $status=200): Response { return Response::html($this->views->render($template,$data+['csrf_token'=>$this->csrf->token(),'message'=>$this->session->pull('private-messages.message'),'error'=>$this->session->pull('private-messages.error')]),$status); }
    private function user(): ?array { return $this->auth->user(); }
    private function id(mixed $value): int { $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new RuntimeException('Invalid identifier.');return(int)$id; }
}
