<?php

declare(strict_types=1);

namespace NovaNuke\Core\Membership;

use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Events\EventName;
use PDO;

final class MembershipExpirationProcessor
{
    public function __construct(
        private readonly PDO $database,
        private readonly EventDispatcher $events,
    ) {}

    public function process(bool $dryRun = false): int
    {
        $statement=$this->database->query(
            "SELECT id,user_id,COALESCE(plan_key,'vip-custom') plan_key,expires_at "
            . "FROM user_entitlements WHERE entitlement='vip' AND revoked_at IS NULL "
            . "AND expires_at IS NOT NULL AND expires_at<=UTC_TIMESTAMP() AND expired_event_at IS NULL "
            . "ORDER BY id LIMIT 500"
        );
        $rows=$statement->fetchAll();
        if($dryRun) return count($rows);

        $mark=$this->database->prepare(
            'UPDATE user_entitlements SET expired_event_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() '
            . 'WHERE id=:id AND expired_event_at IS NULL'
        );

        $processed=0;
        foreach($rows as $row){
            $mark->execute(['id'=>(int)$row['id']]);
            if($mark->rowCount()!==1) continue;

            $this->events->dispatch(EventName::MEMBERSHIP_EXPIRED,new MembershipExpired(
                (int)$row['id'],
                (int)$row['user_id'],
                (string)$row['plan_key'],
                (string)$row['expires_at'],
            ));
            $processed++;
        }

        return $processed;
    }
}
