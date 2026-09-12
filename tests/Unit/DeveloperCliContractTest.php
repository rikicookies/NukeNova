<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DeveloperCliContractTest extends TestCase
{
    public function testModuleCheckRunsBeforeApplicationBootstrapAndDoesNotRequireDatabaseServices(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        $check = strpos($cli, "if (\$command === 'module:check')");
        $bootstrap = strpos($cli, "\$application = require \$rootPath . '/bootstrap/app.php';");

        self::assertNotFalse($check);
        self::assertNotFalse($bootstrap);
        self::assertLessThan($bootstrap, $check);

        $section = substr($cli, $check, $bootstrap - $check);
        self::assertStringContainsString('ModuleDiagnostic', $section);
        self::assertStringNotContainsString('PDO', $section);
        self::assertStringNotContainsString('ModuleManager', $section);
        self::assertStringNotContainsString('->install(', $section);
        self::assertStringNotContainsString('->boot(', $section);
    }

    public function testInspectionCommandsRunBeforeApplicationBootstrap(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        $bootstrap = strpos($cli, "$application = require $rootPath . '/bootstrap/app.php';");
        foreach (["if ($command === 'module:inspect')", "if ($command === 'module:list')"] as $needle) {
            $position = strpos($cli, $needle);
            self::assertNotFalse($position);
            self::assertLessThan($bootstrap, $position);
        }
    }

    public function testHelpDocumentsDeveloperCommands(): void
    {
        $cli = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        self::assertStringContainsString('module:make NAME', $cli);
        self::assertStringContainsString('module:check MODULE', $cli);
        self::assertStringContainsString('module:inspect MODULE', $cli);
        self::assertStringContainsString('module:list', $cli);
    }
}
