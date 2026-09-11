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

        self::assertStringContainsString("\$status['missing_files'] !== []", $source);
        self::assertLessThan(strpos($source, '$migration = require $file'), strpos($source, "\$status['missing_files'] !== []"));
        self::assertStringContainsString('No later migration was run.', $source);
    }

    public function testCliReportsRecoveryWithoutAttemptingAutomaticRollback(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        $start = strpos($source, "if (\$command === 'migrate')");
        $end = strpos($source, "if (\$command === 'migrate:status')", $start);
        $section = substr($source, $start, $end - $start);

        self::assertStringContainsString('Do not retry blindly.', $section);
        self::assertStringContainsString('getPrevious()', $section);
        self::assertStringContainsString('matching pre-upgrade database and files', $section);
        self::assertStringNotContainsString('->down(', $section);
    }
}
