<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Events\EventName;
use NovaNuke\Core\Membership\MembershipActivationProcessor;
use NovaNuke\Core\Membership\MembershipExpirationProcessor;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use RuntimeException;

final class MembershipEventRecoveryIntegrationTest extends MySqlIntegrationTestCase
{
    public function testFailedActivationListenerLeavesMarkerRetryableAndNextRunCompletesOnce(): void
    {
        $grant=$this->grant('activation-retry',false);
        $events=new EventDispatcher(); $attempts=0;
        $events->listen(EventName::MEMBERSHIP_ACTIVATED,static function() use (&$attempts): void{
            $attempts++; if($attempts===1) throw new RuntimeException('Injected activation listener failure.');
        });
        $processor=new MembershipActivationProcessor($this->db(),$events);
        try{$processor->process(false);self::fail('Listener failure was ignored.');}
        catch(RuntimeException $error){self::assertSame('Injected activation listener failure.',$error->getMessage());}
        self::assertNull($this->marker($grant,'activated_event_at'));
        self::assertSame(1,$processor->process(false));
        self::assertNotNull($this->marker($grant,'activated_event_at'));
        self::assertSame(0,$processor->process(false));
        self::assertSame(2,$attempts);
    }

    public function testFailedExpirationListenerLeavesMarkerRetryableAndNextRunCompletesOnce(): void
    {
        $grant=$this->grant('expiration-retry',true);
        $events=new EventDispatcher(); $attempts=0;
        $events->listen(EventName::MEMBERSHIP_EXPIRED,static function() use (&$attempts): void{
            $attempts++; if($attempts===1) throw new RuntimeException('Injected expiration listener failure.');
        });
        $processor=new MembershipExpirationProcessor($this->db(),$events);
        try{$processor->process(false);self::fail('Listener failure was ignored.');}
        catch(RuntimeException $error){self::assertSame('Injected expiration listener failure.',$error->getMessage());}
        self::assertNull($this->marker($grant,'expired_event_at'));
        self::assertSame(1,$processor->process(false));
        self::assertNotNull($this->marker($grant,'expired_event_at'));
        self::assertSame(0,$processor->process(false));
        self::assertSame(2,$attempts);
    }

    private function grant(string $username,bool $expired): int
    {
        $statement=$this->db()->prepare(
            "INSERT INTO users(username,email,password_hash,status,email_verified_at,created_at,updated_at) "
            . "VALUES(:username,:email,:password,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $statement->execute(['username'=>$username,'email'=>$username.'@example.test','password'=>password_hash('Password-93!',PASSWORD_DEFAULT)]);
        $user=(int)$this->db()->lastInsertId();
        $expires=$expired?'DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE)':'DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY)';
        $activated=$expired?'UTC_TIMESTAMP()':'NULL';
        $this->db()->exec(
            "INSERT INTO user_entitlements(user_id,entitlement,plan_key,source,starts_at,expires_at,activated_event_at,expired_event_at,created_at,updated_at) "
            . "VALUES({$user},'vip','vip-30','manual',DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE),{$expires},{$activated},NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        return (int)$this->db()->lastInsertId();
    }

    private function marker(int $grant,string $column): mixed
    {
        self::assertContains($column,['activated_event_at','expired_event_at']);
        return $this->db()->query("SELECT {$column} FROM user_entitlements WHERE id={$grant}")->fetchColumn() ?: null;
    }
}
