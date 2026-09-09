<?php
declare(strict_types=1);
namespace NovaNuke\Core\Access;
use InvalidArgumentException;
final class AccessAudience
{
    public const VALUES=['public','guest','member','vip'];
    public function __construct(private readonly EntitlementService $entitlements){}
    /** @param array<string,mixed>|null $user */
    public function allows(string $audience,?array $user):bool
    {
        if(!in_array($audience,self::VALUES,true))throw new InvalidArgumentException('Invalid access audience.');
        return match($audience){'public'=>true,'guest'=>$user===null,'member'=>$user!==null,'vip'=>$user!==null&&$this->entitlements->has((int)$user['id'],EntitlementService::VIP)};
    }
}
