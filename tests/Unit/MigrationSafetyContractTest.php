<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MigrationSafetyContractTest extends TestCase
{
    public function testCoreMigratorBlocksMissingHistoryBeforeLoadingPendingFiles(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Database/Migrator.php');

        self::assertStringContainsString("\$status['missing_files'] !== []", $source);
        self::assertLessThan(strpos($source, '$migration = require $file'), strpos($source, "\$status['missing_files'] !== []"));
        self::assertStringContainsString('No later migration was run.', $source);
    }

    public function testModuleMigratorUsesTheSameStopBeforeContinueRule(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Modules/ModuleMigrator.php');
        $executeStart = strpos($source, 'private function execute(');
        $rollbackStart = strpos($source, 'public function rollbackAll(', $executeStart);

        self::assertNotFalse($executeStart);
        self::assertNotFalse($rollbackStart);
        $execute = substr($source, $executeStart, $rollbackStart - $executeStart);

        self::assertStringContainsString("\$status['missing_files'] !== []", $execute);
        self::assertLessThan(strpos($execute, '$migration = require $file'), strpos($execute, "\$status['missing_files'] !== []"));
        self::assertStringContainsString('No later migration was run.', $execute);
    }

    public function testCliExposesExplicitRecoveryAndNeverAttemptsAutomaticCoreRollback(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        $start = strpos($source, "if (\$command === 'migrate')");
        $end = strpos($source, "if (\$command === 'migrate:status')", $start);
        $section = substr($source, $start, $end - $start);

        self::assertStringContainsString('migrate:recover', $section);
        self::assertStringContainsString('getPrevious()', $section);
        self::assertStringNotContainsString('->down(', $section);
    }

    public function testRecoveryUsesDurableStatesChecksumsAndSharedMysqlLock(): void
    {
        $root=dirname(__DIR__,2);
        $store=(string)file_get_contents($root.'/app/Core/Database/MigrationOperationStore.php');
        $executor=(string)file_get_contents($root.'/app/Core/Database/MigrationExecutor.php');
        $lock=(string)file_get_contents($root.'/app/Core/Database/MigrationLock.php');

        self::assertStringContainsString('migration_operations',$store);
        self::assertStringContainsString("'running'",$store);
        self::assertStringContainsString("'dirty'",$store);
        self::assertStringContainsString("'completed'",$store);
        self::assertStringContainsString("hash_file('sha256'",$executor);
        self::assertStringContainsString('GET_LOCK',$lock);
        self::assertStringContainsString('RELEASE_LOCK',$lock);
    }

    public function testEveryBundledMigrationDeclaresRecoverablePostconditions(): void
    {
        $root=dirname(__DIR__,2);
        $files=array_merge(
            glob($root.'/database/migrations/*.php')?:[],
            glob($root.'/modules/*/database/migrations/*.php')?:[],
        );
        self::assertNotEmpty($files);
        foreach($files as$file){
            $migration=require$file;
            self::assertInstanceOf(\NovaNuke\Core\Database\RecoverableMigration::class,$migration,$file);
        }
    }
}
