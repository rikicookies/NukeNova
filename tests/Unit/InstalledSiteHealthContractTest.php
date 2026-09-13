<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InstalledSiteHealthContractTest extends TestCase
{
    public function testInstalledSiteCheckUsesInstalledStateInsteadOfInstallerAbsenceRules(): void
    {
        $root=dirname(__DIR__,2);
        $health=(string)file_get_contents($root.'/app/Core/System/InstalledSiteHealthCheck.php');
        $cli=(string)file_get_contents($root.'/bin/cms');

        self::assertStringContainsString("if (\$command === 'site:check')",$cli);
        self::assertStringContainsString('Environment file',$health);
        self::assertStringContainsString('Installation lock',$health);
        self::assertStringContainsString('Recorded core version',$health);
        self::assertStringContainsString('Core and module migrations',$health);
        self::assertStringContainsString('Installed module versions',$health);

        self::assertStringNotContainsString('No existing environment file',$health);
        self::assertStringNotContainsString('No installation lock',$health);
        self::assertStringNotContainsString('StorageProvisioner',$health);
    }

    public function testInstalledSiteCheckCoversRuntimeDirectoriesPreviouslyRequiredManually(): void
    {
        $root=dirname(__DIR__,2);
        $health=(string)file_get_contents($root.'/app/Core/System/InstalledSiteHealthCheck.php');

        foreach([
            'storage/private/downloads',
            'storage/private/backups',
            'storage/private/avatars',
            'storage/private/wiki',
        ] as $directory){
            self::assertStringContainsString("'{$directory}'",$health);
        }
    }

    public function testComposerSeparatesInstallSiteAndProductionReleaseChecks(): void
    {
        $root=dirname(__DIR__,2);
        $composer=json_decode((string)file_get_contents($root.'/composer.json'),true,32,JSON_THROW_ON_ERROR);
        $scripts=$composer['scripts'];

        self::assertSame(['@php bin/cms install:check'],$scripts['check:install']);

        self::assertSame([
            '@php bin/cms site:check',
            '@php bin/cms membership:check',
            '@php bin/cms payment:check',
            '@php bin/cms mail:check',
        ],$scripts['check:site']);

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
        ],$scripts['check:release']);

        self::assertNotContains('@php bin/cms install:check',$scripts['check:site']);
        self::assertNotContains('@php bin/cms install:check',$scripts['check:release']);
    }
}
