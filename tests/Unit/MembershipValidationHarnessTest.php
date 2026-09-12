<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MembershipValidationHarnessTest extends TestCase
{
    public function testComposerExposesFocusedMembershipValidation(): void
    {
        $root=dirname(__DIR__,2);
        $composer=json_decode((string)file_get_contents($root.'/composer.json'),true,32,JSON_THROW_ON_ERROR);

        self::assertSame('@php tests/run-membership-validation.php',$composer['scripts']['test:membership']??null);
        self::assertFileExists($root.'/tests/Integration/MembershipLifecycleIntegrationTest.php');
    }

    public function testMembershipCheckIsReadOnlyAndAvailableFromCli(): void
    {
        $root=dirname(__DIR__,2);
        $health=(string)file_get_contents($root.'/app/Core/Membership/MembershipHealthCheck.php');
        $cli=(string)file_get_contents($root.'/bin/cms');

        self::assertStringContainsString("if (\$command === 'membership:check')",$cli);
        self::assertStringContainsString('Single active VIP per user',$health);
        self::assertStringContainsString('Single scheduled VIP per user',$health);
        self::assertStringContainsString('No active/future overlap',$health);
        self::assertStringContainsString('No persisted VIP boolean',$health);
        self::assertStringNotContainsString('UPDATE user_entitlements',$health);
        self::assertStringNotContainsString('DELETE FROM user_entitlements',$health);
        self::assertStringNotContainsString('INSERT INTO user_entitlements',$health);
    }
}
