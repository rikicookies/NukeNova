<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReleaseCandidateDeploymentContractTest extends TestCase
{
    public function testAggregateUsesCorrectPaymentNamespaceAndIncludesSiteAndBackupRecovery(): void
    {
        $root=dirname(__DIR__,2);
        $source=(string)file_get_contents($root.'/app/Core/System/ReleaseCandidateDeploymentCheck.php');

        self::assertStringContainsString('use NovaNuke\\Core\\Billing\\PaymentHealthCheck;',$source);
        self::assertStringNotContainsString('NovaNuke\\Core\\Payments\\PaymentHealthCheck',$source);
        self::assertStringContainsString('InstalledSiteHealthCheck $site',$source);
        self::assertStringContainsString('BackupRecoveryCheck $backups',$source);
        self::assertStringContainsString("'group'=>'site'",$source);
        self::assertStringContainsString("'group'=>'backups'",$source);
    }

    public function testBackupRecoveryCliIsExposed(): void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/bin/cms');
        self::assertStringContainsString("if (\$command === 'backup:restore-check')",$source);
        self::assertStringContainsString('BackupRecoveryCheck',$source);
    }
}
