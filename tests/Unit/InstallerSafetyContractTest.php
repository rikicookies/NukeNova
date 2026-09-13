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
        self::assertStringContainsString('@unlink($envPath)', $service);
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
    public function testExistingDatabaseIsTriedBeforeDatabaseCreation(): void
    {
        $service = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Installer/InstallerService.php');
        $methodStart = strpos($service, 'private function connectAndCreateDatabase');
        $methodEnd = strpos($service, 'private function createAdministrator', $methodStart);
        $method = substr($service, $methodStart, $methodEnd - $methodStart);

        self::assertLessThan(
            strpos($method, '$server->exec('),
            strpos($method, '$serverDsn . ";dbname={$databaseName}"'),
            'Installer must first try a pre-provisioned database so shared-hosting users do not need CREATE DATABASE permission.',
        );
        self::assertStringContainsString('$driverCode !== 1049', $method);
        self::assertStringNotContainsString('CREATE DATABASE IF NOT EXISTS', $method);
    }


    public function testUninstalledKernelDoesNotResolveDatabaseBackedGuards(): void
    {
        $application = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Application.php');
        self::assertStringContainsString('$installed ? $c->get(MaintenanceMode::class) : null', $application);
        self::assertStringContainsString('$installed ? $c->get(ModuleRouteAccess::class) : null', $application);

        $kernel = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Http/Kernel.php');
        self::assertStringContainsString('if (! $this->installed)', $kernel);
        self::assertStringContainsString('return $this->dispatch($request);', $kernel);
    }

}
