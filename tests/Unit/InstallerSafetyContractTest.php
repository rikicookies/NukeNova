<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InstallerSafetyContractTest extends TestCase
{
    public function testDatabaseMustBeEmptyBeforeCoreMigrationsRun(): void
    {
        $service = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Installer/InstallerService.php');
        self::assertLessThan(strpos($service, 'new Migrator'), strpos($service, 'assertDatabaseIsEmpty'));
        self::assertStringContainsString('information_schema.tables', $service);
        self::assertStringContainsString('avoid overwriting existing data', $service);
    }

    public function testInstallCheckRunsWithoutBootingTheApplication(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        self::assertLessThan(strpos($cli, "\$application = require"), strpos($cli, "\$command === 'install:check'"));
        self::assertStringContainsString('new RequirementsChecker()', $cli);
    }
}
