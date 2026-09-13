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
use NovaNuke\Core\Membership\MembershipExtended;
use NovaNuke\Core\Membership\MembershipPlanCatalog;
use NovaNuke\Core\Membership\MembershipRepository;
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
        self::assertGreaterThan($before->getTimestamp(),$after->getTimestamp());
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

    public function testFreeAccountCannotBeExtendedIntoVip(): void
    {
        $user=$this->user('member-free-extend');
        $actor=$this->user('member-free-extend-admin');
        [$service]=$this->service();

        self::assertFalse($service->status($user)['vip']);

        try {
            $service->extendDays($user,30,$actor);
            self::fail('A Free account must not become VIP through extendDays().');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('Only an active VIP membership can be extended.', $error->getMessage());
        }

        self::assertFalse($service->status($user)['vip']);
        self::assertFalse($service->isVip($user));
    }

    public function testImmediateAssignmentCancelsFutureGrantAndEmitsCancellationOnce(): void
    {
        $user=$this->user('member-replace-scheduled');
        $actor=$this->user('member-replace-scheduled-admin');
        [$service,$events]=$this->service();

        $cancelled=[];
        $assigned=[];
        $events->listen(EventName::MEMBERSHIP_SCHEDULE_CANCELLED,static function(object $event) use (&$cancelled): void{$cancelled[]=$event;});
        $events->listen(EventName::MEMBERSHIP_ASSIGNED,static function(object $event) use (&$assigned): void{$assigned[]=$event;});

        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->modify('+5 days')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-90',$future,$actor);
        self::assertNotNull($service->nextScheduled($user));

        $status=$service->assign($user,'vip-30',$actor);

        self::assertTrue($status['vip']);
        self::assertSame('vip-30',$status['plan_key']);
        self::assertNull($service->nextScheduled($user));
        self::assertCount(1,$cancelled);
        self::assertInstanceOf(MembershipScheduleCancelled::class,$cancelled[0]);
        self::assertCount(1,$assigned);
        self::assertInstanceOf(MembershipAssigned::class,$assigned[0]);
    }

    public function testAssigningFreeRevokesCurrentAndCancelsFutureGrant(): void
    {
        $user=$this->user('member-free-reset');
        $actor=$this->user('member-free-reset-admin');
        [$service,$events]=$this->service();

        $revoked=[];
        $cancelled=[];
        $events->listen(EventName::MEMBERSHIP_REVOKED,static function(object $event) use (&$revoked): void{$revoked[]=$event;});
        $events->listen(EventName::MEMBERSHIP_SCHEDULE_CANCELLED,static function(object $event) use (&$cancelled): void{$cancelled[]=$event;});

        $service->assign($user,'vip-30',$actor);
        $active=$service->status($user);
        $future=(new \DateTimeImmutable((string)$active['expires_at'],new \DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-90',$future,$actor);

        $free=$service->assign($user,'free',$actor);

        self::assertFalse($free['vip']);
        self::assertSame('free',$free['plan_key']);
        self::assertNull($service->nextScheduled($user));
        self::assertCount(1,$revoked);
        self::assertInstanceOf(MembershipRevoked::class,$revoked[0]);
        self::assertCount(1,$cancelled);
        self::assertInstanceOf(MembershipScheduleCancelled::class,$cancelled[0]);
    }

    public function testScheduledGrantCanBeCancelledWithoutAffectingCurrentVip(): void
    {
        $user=$this->user('member-cancel');
        $actor=$this->user('member-admin5');
        [$service,$events]=$this->service();
        $cancelled=[];
        $events->listen(EventName::MEMBERSHIP_SCHEDULE_CANCELLED,static function(object $event) use (&$cancelled): void{$cancelled[]=$event;});

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
        self::assertCount(1,$cancelled);
        self::assertInstanceOf(MembershipScheduleCancelled::class,$cancelled[0]);
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


    public function testSchedulingRejectsPastStartsAndLifetimeOverlap(): void
    {
        $user=$this->user('member-invalid-schedule');
        $actor=$this->user('member-invalid-schedule-admin');
        [$service]=$this->service();

        $past=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))
            ->modify('-1 day')->format('Y-m-d H:i:s');

        try {
            $service->schedule($user,'vip-30',$past,$actor);
            self::fail('Past scheduling should have been rejected.');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('must start in the future',$error->getMessage());
        }

        $service->assign($user,'vip-lifetime',$actor);
        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))
            ->modify('+30 days')->format('Y-m-d H:i:s');

        try {
            $service->schedule($user,'vip-90',$future,$actor);
            self::fail('Future scheduling over Lifetime VIP should have been rejected.');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('Lifetime VIP',$error->getMessage());
        }

        self::assertTrue($service->status($user)['lifetime']);
        self::assertNull($service->nextScheduled($user));
    }

    public function testActivationAndExpirationDryRunsDoNotMutateOrDispatch(): void
    {
        $user=$this->user('member-dry-run');
        $actor=$this->user('member-dry-run-admin');
        [$service,$events]=$this->service();

        $activated=0;
        $expired=0;
        $events->listen(EventName::MEMBERSHIP_ACTIVATED,static function() use (&$activated): void{$activated++;});
        $events->listen(EventName::MEMBERSHIP_EXPIRED,static function() use (&$expired): void{$expired++;});

        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))
            ->modify('+2 days')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-30',$future,$actor);

        $this->db()->prepare(
            "UPDATE user_entitlements SET starts_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE),"
            . "expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY),activated_event_at=NULL "
            . "WHERE user_id=:user AND entitlement='vip' AND revoked_at IS NULL"
        )->execute(['user'=>$user]);

        $activation=new MembershipActivationProcessor($this->db(),$events);
        self::assertSame(1,$activation->process(true));
        self::assertSame(0,$activated);

        $activationMarker=$this->db()->prepare(
            "SELECT activated_event_at FROM user_entitlements WHERE user_id=:user AND entitlement='vip' AND revoked_at IS NULL"
        );
        $activationMarker->execute(['user'=>$user]);
        self::assertNull($activationMarker->fetchColumn());

        self::assertSame(1,$activation->process(false));
        self::assertSame(1,$activated);

        $this->db()->prepare(
            "UPDATE user_entitlements SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE),expired_event_at=NULL "
            . "WHERE user_id=:user AND entitlement='vip' AND revoked_at IS NULL"
        )->execute(['user'=>$user]);

        $expiration=new MembershipExpirationProcessor($this->db(),$events);
        self::assertSame(1,$expiration->process(true));
        self::assertSame(0,$expired);

        $expirationMarker=$this->db()->prepare(
            "SELECT expired_event_at FROM user_entitlements WHERE user_id=:user AND entitlement='vip' AND revoked_at IS NULL"
        );
        $expirationMarker->execute(['user'=>$user]);
        self::assertNull($expirationMarker->fetchColumn());

        self::assertSame(1,$expiration->process(false));
        self::assertSame(1,$expired);
    }


    public function testRepeatedRevokeAndScheduleCancellationAreIdempotent(): void
    {
        $user=$this->user('member-idempotent-actions');
        $actor=$this->user('member-idempotent-admin');
        [$service,$events]=$this->service();

        $revoked=0;
        $cancelled=0;
        $events->listen(EventName::MEMBERSHIP_REVOKED,static function() use (&$revoked): void{$revoked++;});
        $events->listen(EventName::MEMBERSHIP_SCHEDULE_CANCELLED,static function() use (&$cancelled): void{$cancelled++;});

        $service->assign($user,'vip-30',$actor);
        $service->revoke($user,$actor);
        $service->revoke($user,$actor);

        self::assertFalse($service->isVip($user));
        self::assertSame(1,$revoked);

        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))
            ->modify('+10 days')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-90',$future,$actor);

        self::assertTrue($service->cancelScheduled($user,$actor));
        self::assertFalse($service->cancelScheduled($user,$actor));
        self::assertNull($service->nextScheduled($user));
        self::assertSame(1,$cancelled);
    }

    public function testReplacingVipRevokesPriorGrantAndKeepsOnlyOneActiveGrant(): void
    {
        $user=$this->user('member-replace-active');
        $actor=$this->user('member-replace-active-admin');
        [$service]=$this->service();

        $service->assign($user,'vip-30',$actor,'first');
        $first=$service->status($user);
        self::assertSame('vip-30',$first['plan_key']);

        $service->assign($user,'vip-90',$actor,'upgrade');
        $second=$service->status($user);

        self::assertTrue($second['vip']);
        self::assertSame('vip-90',$second['plan_key']);

        $statement=$this->db()->prepare(
            "SELECT COUNT(*) FROM user_entitlements WHERE user_id=:user AND entitlement='vip' "
            . "AND revoked_at IS NULL AND starts_at<=UTC_TIMESTAMP() "
            . "AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP())"
        );
        $statement->execute(['user'=>$user]);
        self::assertSame(1,(int)$statement->fetchColumn());

        $history=$this->db()->prepare(
            "SELECT plan_key,revoked_at FROM user_entitlements WHERE user_id=:user AND entitlement='vip' ORDER BY id"
        );
        $history->execute(['user'=>$user]);
        $rows=$history->fetchAll();

        self::assertCount(2,$rows);
        self::assertSame('vip-30',$rows[0]['plan_key']);
        self::assertNotNull($rows[0]['revoked_at']);
        self::assertSame('vip-90',$rows[1]['plan_key']);
        self::assertNull($rows[1]['revoked_at']);
    }

    public function testUnknownPlanAndInvalidDurationsFailWithoutChangingState(): void
    {
        $user=$this->user('member-invalid-inputs');
        $actor=$this->user('member-invalid-inputs-admin');
        [$service]=$this->service();

        foreach ([
            static fn()=>$service->assign($user,'vip-does-not-exist',$actor),
            static fn()=>$service->grantDays($user,0,$actor),
            static fn()=>$service->grantDays($user,3651,$actor),
        ] as $operation) {
            try {
                $operation();
                self::fail('Invalid membership operation should have failed.');
            } catch (InvalidArgumentException) {
            }
            self::assertFalse($service->isVip($user));
            self::assertSame('free',$service->status($user)['plan_key']);
        }
    }


    public function testMembershipHealthCheckAcceptsNormalReplacementHistory(): void
    {
        $user=$this->user('member-health-valid-history');
        $actor=$this->user('member-health-valid-admin');
        [$service]=$this->service();

        $service->assign($user,'vip-30',$actor);
        $service->assign($user,'vip-90',$actor);

        $checks=(new MembershipHealthCheck($this->db()))->run();
        $byName=[];
        foreach($checks as $check) $byName[$check['name']]=$check;

        self::assertTrue($byName['Single active VIP per user']['passed']);
        self::assertTrue($byName['Single scheduled VIP per user']['passed']);
        self::assertTrue($byName['No active/future overlap']['passed']);
        self::assertTrue($byName['Known plan keys']['passed']);
    }


    public function testLegacyCustomDayGrantCannotExtendAScheduledFutureGrant(): void
    {
        $user=$this->user('member-legacy-days');
        $actor=$this->user('member-legacy-days-admin');
        [$service,$events]=$this->service();

        $cancelled=0;
        $events->listen(EventName::MEMBERSHIP_SCHEDULE_CANCELLED,static function() use (&$cancelled): void{$cancelled++;});

        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))
            ->modify('+20 days')->format('Y-m-d H:i:s');
        $service->schedule($user,'vip-90',$future,$actor);
        self::assertFalse($service->isVip($user));
        self::assertNotNull($service->nextScheduled($user));

        $status=$service->grantDays($user,45,$actor,'legacy custom duration');

        self::assertTrue($status['vip']);
        self::assertSame('vip-custom',$status['plan_key']);
        self::assertTrue($service->isVip($user));
        self::assertNull($service->nextScheduled($user));
        self::assertSame(1,$cancelled);

        $statement=$this->db()->prepare(
            "SELECT COUNT(*) FROM user_entitlements WHERE user_id=:user AND entitlement='vip' "
            . "AND revoked_at IS NULL AND starts_at<=UTC_TIMESTAMP() "
            . "AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP())"
        );
        $statement->execute(['user'=>$user]);
        self::assertSame(1,(int)$statement->fetchColumn());
    }


    public function testExtensionEmitsDedicatedEventInsteadOfSecondAssignment(): void
    {
        $user=$this->user('member-extended-event');
        $actor=$this->user('member-extended-event-admin');
        [$service,$events]=$this->service();

        $assigned=0;
        $extended=[];
        $events->listen(EventName::MEMBERSHIP_ASSIGNED,static function() use (&$assigned): void{$assigned++;});
        $events->listen(EventName::MEMBERSHIP_EXTENDED,static function(object $event) use (&$extended): void{$extended[]=$event;});

        $service->assign($user,'vip-30',$actor);
        $status=$service->extendDays($user,30,$actor,'renewed');

        self::assertSame(1,$assigned);
        self::assertCount(1,$extended);
        self::assertInstanceOf(MembershipExtended::class,$extended[0]);
        self::assertSame(30,$extended[0]->days);
        self::assertSame('vip-30',$extended[0]->planKey);
        self::assertSame($status['expires_at'],$extended[0]->expiresAt);
    }


    public function testReplacementAndRevocationRemainVisibleInMembershipHistory(): void
    {
        $user=$this->user('member-history');
        $actor=$this->user('member-history-admin');
        [$service]=$this->service();

        $service->assign($user,'vip-30',$actor,'initial');
        $service->assign($user,'vip-90',$actor,'upgrade');
        $service->revoke($user,$actor);

        $repository=new MembershipRepository($this->db());
        $history=$repository->history($user);

        self::assertGreaterThanOrEqual(2,count($history));
        self::assertSame('vip-90',$history[0]['plan_key']);
        self::assertNotNull($history[0]['revoked_at']);
        self::assertSame('vip-30',$history[1]['plan_key']);
        self::assertNotNull($history[1]['revoked_at']);
    }

    public function testMembershipOverviewSeparatesNeverInactiveActiveLifetimeAndScheduled(): void
    {
        $actor=$this->user('member-overview-admin');
        $never=$this->user('member-overview-never');
        $inactive=$this->user('member-overview-inactive');
        $active=$this->user('member-overview-active');
        $lifetime=$this->user('member-overview-lifetime');
        $scheduled=$this->user('member-overview-scheduled');
        [$service]=$this->service();

        $service->assign($inactive,'vip-30',$actor);
        $service->revoke($inactive,$actor);
        $service->assign($active,'vip-30',$actor);
        $service->assign($lifetime,'vip-lifetime',$actor);
        $future=(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->modify('+5 days')->format('Y-m-d H:i:s');
        $service->schedule($scheduled,'vip-90',$future,$actor);

        $repository=new MembershipRepository($this->db());
        $counts=$repository->overview('all','')['counts'];

        self::assertGreaterThanOrEqual(1,(int)$counts['never']);
        self::assertGreaterThanOrEqual(1,(int)$counts['inactive']);
        self::assertGreaterThanOrEqual(2,(int)$counts['active']);
        self::assertGreaterThanOrEqual(1,(int)$counts['lifetime']);
        self::assertGreaterThanOrEqual(1,(int)$counts['scheduled']);

        self::assertNotNull($repository->user($never));
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
