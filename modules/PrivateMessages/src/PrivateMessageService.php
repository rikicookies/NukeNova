<?php

declare(strict_types=1);

namespace Modules\PrivateMessages\src;

use NovaNuke\Core\Security\DatabaseRateLimiter;
use RuntimeException;

final class PrivateMessageService
{
    public function __construct(private readonly PrivateMessageRepository $repository,private readonly PrivateMessageInput $input,private readonly DatabaseRateLimiter $sendLimiter,private readonly DatabaseRateLimiter $reportLimiter) {}

    public function compose(int $sender,string $username,mixed $subject,mixed $body): int
    {
        $username=$this->input->recipient($username);$subject=$this->input->subject($subject);$body=$this->input->body($body);
        $recipient=$this->repository->userByUsername($username); if($recipient===null)throw new RuntimeException('Recipient was not found.');
        $recipientId=(int)$recipient['id']; if($recipientId===$sender)throw new RuntimeException('You cannot message yourself.');
        $this->assertCanSend($sender,$recipientId); return $this->repository->create($sender,$recipientId,$subject,$body);
    }

    public function reply(int $conversation,int $sender,mixed $body): int
    {
        $body=$this->input->body($body);
        $thread=$this->repository->conversation($conversation,$sender); if($thread===null)throw new RuntimeException('Conversation not found.');
        $this->assertCanSend($sender,(int)$thread['other']['id']); return $this->repository->reply($conversation,$sender,$body);
    }

    public function report(int $message,int $user,mixed $reason): void
    {
        $key='user:'.$user; if($this->reportLimiter->tooManyAttempts($key))throw new RuntimeException('Too many reports. Please wait before trying again.');
        $reason=$this->input->reason($reason);
        $this->repository->report($message,$user,$reason);$this->reportLimiter->hit($key);
    }

    private function assertCanSend(int $sender,int $recipient): void
    {
        if($this->repository->blocked($sender,$recipient))throw new RuntimeException('Messaging is unavailable between these users.');
        $key='user:'.$sender;if($this->sendLimiter->tooManyAttempts($key))throw new RuntimeException('Too many messages. Please wait before trying again.');$this->sendLimiter->hit($key);
    }
}
