<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Security\UserRoleSafety;
use PHPUnit\Framework\TestCase;

final class UserRoleSafetyTest extends TestCase
{
    public function testItPreventsSelfModification(): void
    {
        self::assertNotNull((new UserRoleSafety())->violation(1, 1, true, true, true, true, 1));
    }

    public function testItProtectsSuperAdministratorsFromOrdinaryAdministrators(): void
    {
        self::assertNotNull((new UserRoleSafety())->violation(2, 1, false, true, true, true, 2));
        self::assertNotNull((new UserRoleSafety())->violation(2, 3, false, false, true, true, 2));
    }

    public function testItPreventsRemovingTheFinalActiveSuperAdministrator(): void
    {
        self::assertNotNull((new UserRoleSafety())->violation(2, 1, true, true, false, true, 1));
        self::assertNotNull((new UserRoleSafety())->violation(2, 1, true, true, true, false, 1));
    }

    public function testItAllowsSafeAdministrativeChanges(): void
    {
        self::assertNull((new UserRoleSafety())->violation(1, 2, true, false, true, false, 1));
        self::assertNull((new UserRoleSafety())->violation(1, 2, true, false, true, true, 1));
    }
}
