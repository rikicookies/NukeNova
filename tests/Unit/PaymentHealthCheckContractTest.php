<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PaymentHealthCheckContractTest extends TestCase
{
    public function testPaymentCheckIsReadOnlyAndExposedByCli(): void
    {
        $root=dirname(__DIR__,2);
        $health=(string)file_get_contents($root.'/app/Core/Billing/PaymentHealthCheck.php');
        $cli=(string)file_get_contents($root.'/bin/cms');

        self::assertStringContainsString("if (\$command === 'payment:check')",$cli);
        self::assertStringContainsString('Receipt idempotency',$health);
        self::assertStringContainsString('Known receipt plan keys',$health);
        self::assertStringContainsString('Registered payment providers',$health);
        self::assertStringNotContainsString('INSERT INTO payment_receipts',$health);
        self::assertStringNotContainsString('UPDATE payment_receipts',$health);
        self::assertStringNotContainsString('DELETE FROM payment_receipts',$health);
    }

    public function testReleaseCheckIncludesPaymentIntegrityAudit(): void
    {
        $root=dirname(__DIR__,2);
        $composer=json_decode((string)file_get_contents($root.'/composer.json'),true,32,JSON_THROW_ON_ERROR);
        self::assertContains('@php bin/cms payment:check',$composer['scripts']['check:release']??[]);
    }
}
