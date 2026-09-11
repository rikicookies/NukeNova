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
        $end = strpos($cli, "if (in_array(\$command", $start);
        $section = substr($cli, $start, $end - $start);

        self::assertStringContainsString("count(\$arguments) !== 1", $section);
        self::assertStringContainsString("str_starts_with(\$arguments[0], '--from=')", $section);
        self::assertStringContainsString('MigrationStatus::class', $section);
        self::assertStringContainsString("'0.2.0-alpha.36'", $section);
        self::assertStringContainsString("'0.2.0-alpha.37'", $section);
        self::assertStringContainsString("'0.2.0-alpha.38'", $section);
        self::assertStringContainsString('UpgradeReadiness', $section);
        self::assertStringNotContainsString('->run(', $section);
        self::assertStringNotContainsString('backup:database', $section);
    }
}
