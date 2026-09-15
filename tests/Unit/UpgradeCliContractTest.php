<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class UpgradeCliContractTest extends TestCase
{
    public function testUpgradeCheckIsReadOnlyAndRequiresAnExplicitSource(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        $start = strpos($cli, "if (\$command === 'upgrade:check')");
        $end = strpos($cli, "if (\$command === 'upgrade:complete')", $start);
        $section = substr($cli, $start, $end - $start);

        self::assertStringContainsString("count(\$arguments) !== 1", $section);
        self::assertStringContainsString("str_starts_with(\$arguments[0], '--from=')", $section);
        self::assertStringContainsString('MigrationStatus::class', $section);
        self::assertStringContainsString('$supportedUpgradeSources', $section);
        self::assertStringContainsString("'0.4.0-rc.1', '0.4.0-rc.2', '0.4.0-rc.3', Version::CURRENT", $cli);
        self::assertStringContainsString('UpgradeReadiness', $section);
        self::assertStringContainsString("'system.core_version'", $section);
        self::assertStringNotContainsString('->run(', $section);
        self::assertStringNotContainsString('backup:database', $section);
    }

    public function testUpgradeCompletionWritesOnlyAfterAllRequiredChecksPass(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        $start = strpos($cli, "if (\$command === 'upgrade:complete')");
        $end = strpos($cli, "if (in_array(\$command", $start);
        $section = substr($cli, $start, $end - $start);

        self::assertStringContainsString('MigrationStatus::class', $section);
        self::assertStringContainsString('ReleaseChecklist', $section);
        self::assertStringContainsString('StorageProvisioner', $section);
        self::assertStringContainsString("'system.core_version'", $section);
        self::assertLessThan(strpos($section, '$settings->setMany'), strpos($section, 'if (! $passed) exit(1)'));
        self::assertStringNotContainsString('->run(', $section);
        self::assertStringNotContainsString('->down(', $section);
    }
}
