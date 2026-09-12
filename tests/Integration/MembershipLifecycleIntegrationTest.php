<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use InvalidArgumentException;
use NovaNuke\Core\Access\EntitlementService;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Events\EventName;
use NovaNuke\Core\Membership\MembershipActivated;
use NovaNuke\Core\Membership\MembershipActivationProcessor;
use NovaNuke\Core\Membership\MembershipAssigned;
use NovaNuke\Core\Membership\MembershipExpirationProcessor;
use NovaNuke\Core\Membership\MembershipHealthCheck;
use NovaNuke\Core\Membership\MembershipExpired;
use NovaNuke\Core\Membership\MembershipPlanCatalog;
use NovaNuke\Core\Membership\MembershipRevoked;
use NovaNuke\Core\Membership\MembershipScheduleCancelled;
use NovaNuke\Core\Membership\MembershipScheduled;
use NovaNuke\Core\Membership\MembershipService;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;

final class MembershipLifecycleIntegrationTest extends MySqlIntegrationTestCase
{
    public function testFreeVipExtendRevokeLifecycle(): void
    {
        $user=$this->user('member-life');
        $actor=$this->user('member-admin');
        [$service,$events]=$this->service();

        $seen=[];
        foreach([EventName::MEMBERSHIP_ASSIGNED,EventName::MEMBERSHIP_REVOKED] as $name){
            $events->listen($name,static function(object $event) use (&$seen,$name): void{$seen[$name][]=$event;});
        }

        $free=$service->status($user);
        self::assertFalse($free['vip']);
        self::assertSame('free',$free['plan_key']);

        $vip=$service->assign($user,'vip-30',$actor,'integration');
        self::assertTrue($vip['vip']);
        self::assertTrue($service->isVip($user));
        self::assertSame('vip-30',$vip['plan_key']);
        $before=new \DateTimeImmutable((string)$vip['expires_at'],new \DateTimeZone('UTC'));

        $extended=$service->extendDays($user,30,$actor,'renewal');
        $after=new \DateTimeImmutable((string)$extended['expires_at'],new \DateTimeZone('UTC'));
        self::assertGreaterThanOrEqual(29,$before->diff($after)->days);
        self::assertSame('vip-30',$extended['plan_key']);

        $service->revoke($user,$actor);
        self::assertFalse($service->isVip($user));
        self::assertFalse($service->status($user)['vip']);

        self::assertNotEmpty($seen[EventName::MEMBERSHIP_ASSIGNED]??[]);
        self::assertInstanceOf(MembershipAssigned::class,$seen[EventName::MEMBERSHIP_ASSIGNED][0]);
        self::assertCount(1,$seen[EventName::MEMBERSHIP_REVOKED]??[]);
        self::assertInstanceOf(MembershipRevoked::class,$seen[EventName::MEMBERSHIP_REVOKED][0]);
    }

    public function testLifetimeCannotBeExtendedAndFreeClearsScheduledAndActiveVip(): void
    {
        $user=$this->user('member-life2');
        $actor=$this->user('member-admin2');
        [$service]=$this->service();

        $service->assign($user,'vip-lifetime',$actor);
        self::assertTrue($service->status($user)['lifetime']);

        try {
            $service->extendDays($user,30,$actor);
            self::fail('Lifetime VIP extension should have been rejected.');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('Lifetime VIP', $error->getMessage());
        }

        $service->assign($user,'free',$actor);
        self::assertFalse($service->isVip($user));

        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->modify('+10 days')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-90',$future,$actor);
        self::assertNotNull($service->nextScheduled($user));

        $service->assign($user,'free',$actor);
        self::assertFalse($service->isVip($user));
        self::assertNull($service->nextScheduled($user));
    }

    public function testScheduledActivationAndExpirationEventsAreIdempotent(): void
    {
        $user=$this->user('member-scheduled');
        $actor=$this->user('member-admin3');
        [$service,$events]=$this->service();

        $seen=['scheduled'=>0,'activated'=>0,'expired'=>0,'cancelled'=>0];
        $events->listen(EventName::MEMBERSHIP_SCHEDULED,static function(object $event) use (&$seen): void{
            self::assertInstanceOf(MembershipScheduled::class,$event);$seen['scheduled']++;
        });
        $events->listen(EventName::MEMBERSHIP_ACTIVATED,static function(object $event) use (&$seen): void{
            self::assertInstanceOf(MembershipActivated::class,$event);$seen['activated']++;
        });
        $events->listen(EventName::MEMBERSHIP_EXPIRED,static function(object $event) use (&$seen): void{
            self::assertInstanceOf(MembershipExpired::class,$event);$seen['expired']++;
        });
        $events->listen(EventName::MEMBERSHIP_SCHEDULE_CANCELLED,static function(object $event) use (&$seen): void{
            self::assertInstanceOf(MembershipScheduleCancelled::class,$event);$seen['cancelled']++;
        });

        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->modify('+2 days')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-30',$future,$actor);
        self::assertSame(1,$seen['scheduled']);
        self::assertFalse($service->isVip($user));

        $this->db()->prepare(
            "UPDATE user_entitlements SET starts_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE),"
            . "expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY),activated_event_at=NULL "
            . "WHERE user_id=:user AND entitlement='vip' AND revoked_at IS NULL"
        )->execute(['user'=>$user]);

        $activation=new MembershipActivationProcessor($this->db(),$events);
        self::assertSame(1,$activation->process(false));
        self::assertSame(0,$activation->process(false));
        self::assertSame(1,$seen['activated']);
        self::assertTrue($service->isVip($user));

        $this->db()->prepare(
            "UPDATE user_entitlements SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE),expired_event_at=NULL "
            . "WHERE user_id=:user AND entitlement='vip' AND revoked_at IS NULL"
        )->execute(['user'=>$user]);

        $expiration=new MembershipExpirationProcessor($this->db(),$events);
        self::assertSame(1,$expiration->process(false));
        self::assertSame(0,$expiration->process(false));
        self::assertSame(1,$seen['expired']);
        self::assertFalse($service->isVip($user));
    }

    public function testSchedulingOverlapAndCancellationRules(): void
    {
        $user=$this->user('member-overlap');
        $actor=$this->user('member-admin4');
        [$service,$events]=$this->service();

        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->modify('+4 days')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-30',$future,$actor);

        $this->expectException(InvalidArgumentException::class);
        $service->schedule(
            $user,
            'vip-90',
            (new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->modify('+8 days')->format('Y-m-d H:i:s'),
            $actor,
        );
    }

    public function testScheduledGrantCanBeCancelledWithoutAffectingCurrentVip(): void
    {
        $user=$this->user('member-cancel');
        $actor=$this->user('member-admin5');
        [$service]=$this->service();

        $service->assign($user,'vip-30',$actor);
        $active=$service->status($user);
        $future=(new \DateTimeImmutable((string)$active['expires_at'],new \DateTimeZone('UTC')))
            ->modify('+1 day')->format('Y-m-d H:i:s');

        $service->schedule($user,'vip-90',$future,$actor);
        self::assertNotNull($service->nextScheduled($user));
        self::assertTrue($service->isVip($user));

        self::assertTrue($service->cancelScheduled($user,$actor));
        self::assertNull($service->nextScheduled($user));
        self::assertTrue($service->isVip($user));
    }


    public function testMembershipHealthCheckDetectsCorruptOverlappingState(): void
    {
        $user=$this->user('member-health');
        $actor=$this->user('member-health-admin');
        [$service]=$this->service();

        $service->assign($user,'vip-30',$actor);
        $this->db()->prepare(
            "INSERT INTO user_entitlements "
            . "(user_id,entitlement,plan_key,source,note,starts_at,activated_event_at,expires_at,granted_by,revoked_at,expired_event_at,created_at,updated_at) "
            . "VALUES (:user,'vip','vip-90','manual','intentional test corruption',UTC_TIMESTAMP(),UTC_TIMESTAMP(),"
            . "DATE_ADD(UTC_TIMESTAMP(),INTERVAL 90 DAY),:actor,NULL,NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        )->execute(['user'=>$user,'actor'=>$actor]);

        $checks=(new MembershipHealthCheck($this->db()))->run();
        $byName=[];
        foreach($checks as $check) $byName[$check['name']]=$check;

        self::assertFalse($byName['Single active VIP per user']['passed']);
    }

    /** @return array{MembershipService,EventDispatcher} */
    private function service(): array
    {
        $events=new EventDispatcher();
        return [
            new MembershipService(
                new EntitlementService($this->db()),
                new MembershipPlanCatalog(),
                $events,
            ),
            $events,
        ];
    }

    private function user(string $username): int
    {
        $statement=$this->db()->prepare(
            'INSERT INTO users (username,email,password_hash,status,email_verified_at,created_at,updated_at) '
            . 'VALUES (:username,:email,:password,\'active\',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $statement->execute([
            'username'=>$username,
            'email'=>$username.'@example.test',
            'password'=>password_hash('Integration-Password-92!',PASSWORD_DEFAULT),
        ]);
        return (int)$this->db()->lastInsertId();
    }
}
