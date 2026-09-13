<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CheckpointValidationContractTest extends TestCase
{
    public function testComposerProvidesCheckpointValidation(): void
    {
        $root=dirname(__DIR__,2);
        $composer=json_decode((string)file_get_contents($root.'/composer.json'),true,32,JSON_THROW_ON_ERROR);
        $script=$composer['scripts']['test:checkpoint']??[];

        self::assertIsArray($script);
        self::assertContains('@php vendor/bin/phpunit --display-warnings --display-skipped',$script);
        self::assertContains('@php tests/run-integration.php',$script);
    }

    public function testMembershipExtensionContractScopesAssertionsToExtendMethod(): void
    {
        $root=dirname(__DIR__,2);
        $test=(string)file_get_contents($root.'/tests/Unit/MembershipSchedulingContractTest.php');

        self::assertStringContainsString("\$extendSource=substr(", $test);
        self::assertStringContainsString("assertStringNotContainsString(\"plan_key='vip-custom'\", \$extendSource)", $test);
        self::assertStringNotContainsString("assertStringNotContainsString(\"SET expires_at=:expires,plan_key='vip-custom'\", \$source)", $test);
    }
    public function testBehaviorSensitiveMembershipSqlAssertionsAreMethodScoped(): void
    {
        $root=dirname(__DIR__,2);
        $scheduling=(string)file_get_contents($root.'/tests/Unit/MembershipSchedulingContractTest.php');
        $stabilization=(string)file_get_contents($root.'/tests/Unit/MembershipBetaStabilizationContractTest.php');

        self::assertStringContainsString('$extendSource=substr(', $scheduling);
        self::assertStringContainsString('$grantSource=substr(', $stabilization);
        self::assertStringContainsString('$replaceSource=substr(', $stabilization);
        self::assertStringContainsString('$scheduleSource=substr(', $stabilization);
        self::assertStringNotContainsString("substr_count(\$source, 'activated_event_at')", $stabilization);
    }

    public function testComposerProvidesReleaseReadinessChecks(): void
    {
        $root=dirname(__DIR__,2);
        $composer=json_decode((string)file_get_contents($root.'/composer.json'),true,32,JSON_THROW_ON_ERROR);
        $script=$composer['scripts']['check:release']??[];

        self::assertSame([
            '@php bin/cms release:check',
            '@php bin/cms release:smoke',
            '@php bin/cms site:check',
            '@php bin/cms production:check',
            '@php bin/cms membership:check',
            '@php bin/cms payment:check',
            '@php bin/cms theme:check',
            '@php bin/cms mail:check',
            '@php bin/cms rc:deployment',
        ],$script);
    }


}
