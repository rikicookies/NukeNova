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


    public function testFailedOwnedInstallationRollsBackSchemaAndEnvironmentArtifacts(): void
    {
        $service = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Installer/InstallerService.php');

        self::assertStringContainsString('rollbackOwnedSchema($database)', $service);
        self::assertStringContainsString("DROP TABLE IF EXISTS", $service);
        self::assertStringContainsString("SET FOREIGN_KEY_CHECKS=0", $service);
        self::assertStringContainsString("@unlink($envPath)", $service);
        self::assertLessThan(
            strpos($service, 'rollbackOwnedSchema($database)'),
            strpos($service, 'assertDatabaseIsEmpty($database)'),
        );
    }

    public function testInstallCheckRunsWithoutBootingTheApplication(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        self::assertLessThan(strpos($cli, "\$application = require"), strpos($cli, "\$command === 'install:check'"));
        self::assertStringContainsString('new RequirementsChecker()', $cli);
    }
}
