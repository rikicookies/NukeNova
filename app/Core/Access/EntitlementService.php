<?php
declare(strict_types=1);
namespace NovaNuke\Core\Access;
use DateTimeImmutable;use DateTimeZone;use InvalidArgumentException;use PDO;
final class EntitlementService
{
    public const VIP='vip';
    public function __construct(private readonly PDO $database){}
    public function has(int $userId,string $entitlement):bool
    {
        $this->assertKey($entitlement);$s=$this->database->prepare('SELECT COUNT(*) FROM user_entitlements WHERE user_id=:user_id AND entitlement=:entitlement AND starts_at<=UTC_TIMESTAMP() AND expires_at>UTC_TIMESTAMP() AND revoked_at IS NULL');$s->execute(['user_id'=>$userId,'entitlement'=>$entitlement]);return(int)$s->fetchColumn()>0;
    }
    /** @return array<string,mixed>|null */
    public function status(int $userId,string $entitlement):?array
    {
        $this->assertKey($entitlement);$s=$this->database->prepare('SELECT id,entitlement,starts_at,expires_at,revoked_at,created_at FROM user_entitlements WHERE user_id=:user_id AND entitlement=:entitlement ORDER BY id DESC LIMIT 1');$s->execute(['user_id'=>$userId,'entitlement'=>$entitlement]);$r=$s->fetch();if(!is_array($r))return null;$r['active']=self::recordIsActive($r);return$r;
    }
    /** @param array<string,mixed> $record */
    public static function recordIsActive(array $record,?DateTimeImmutable $at=null):bool
    {
        if(($record['revoked_at']??null)!==null||!is_string($record['starts_at']??null)||!is_string($record['expires_at']??null))return false;$utc=new DateTimeZone('UTC');$at=($at??new DateTimeImmutable('now',$utc))->setTimezone($utc);return new DateTimeImmutable($record['starts_at'],$utc)<=$at&&new DateTimeImmutable($record['expires_at'],$utc)>$at;
    }
    public function grant(int $userId,string $entitlement,int $days,int $grantedBy):string
    {
        $this->assertKey($entitlement);if($days<1||$days>3650)throw new InvalidArgumentException('VIP duration must be between 1 and 3650 days.');$now=new DateTimeImmutable('now',new DateTimeZone('UTC'));$this->database->beginTransaction();
        try{$lock=$this->database->prepare('SELECT id FROM users WHERE id=:id FOR UPDATE');$lock->execute(['id'=>$userId]);$s=$this->database->prepare('SELECT id,expires_at FROM user_entitlements WHERE user_id=:user_id AND entitlement=:entitlement AND revoked_at IS NULL AND expires_at>UTC_TIMESTAMP() ORDER BY expires_at DESC LIMIT 1 FOR UPDATE');$s->execute(['user_id'=>$userId,'entitlement'=>$entitlement]);$r=$s->fetch();$base=is_array($r)?new DateTimeImmutable((string)$r['expires_at'],new DateTimeZone('UTC')):$now;$expires=$base->modify('+'.$days.' days')->format('Y-m-d H:i:s');if(is_array($r)){$q=$this->database->prepare('UPDATE user_entitlements SET expires_at=:expires,granted_by=:actor,updated_at=UTC_TIMESTAMP() WHERE id=:id');$q->execute(['expires'=>$expires,'actor'=>$grantedBy,'id'=>$r['id']]);}else{$q=$this->database->prepare('INSERT INTO user_entitlements (user_id,entitlement,starts_at,expires_at,granted_by,created_at,updated_at) VALUES (:user_id,:entitlement,UTC_TIMESTAMP(),:expires,:actor,UTC_TIMESTAMP(),UTC_TIMESTAMP())');$q->execute(['user_id'=>$userId,'entitlement'=>$entitlement,'expires'=>$expires,'actor'=>$grantedBy]);}$this->database->commit();return$expires;}catch(\Throwable$e){if($this->database->inTransaction())$this->database->rollBack();throw$e;}
    }
    public function revoke(int $userId,string $entitlement):void
    {
        $this->assertKey($entitlement);$s=$this->database->prepare('UPDATE user_entitlements SET revoked_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE user_id=:user_id AND entitlement=:entitlement AND revoked_at IS NULL AND expires_at>UTC_TIMESTAMP()');$s->execute(['user_id'=>$userId,'entitlement'=>$entitlement]);
    }
    private function assertKey(string $key):void{if(!preg_match('/^[a-z][a-z0-9.-]{1,63}$/',$key))throw new InvalidArgumentException('Invalid entitlement key.');}
}
