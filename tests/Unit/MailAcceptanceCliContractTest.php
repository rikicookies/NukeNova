<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MailAcceptanceCliContractTest extends TestCase
{
    public function testMailAcceptanceCommandIsExposedAndUsesDurableAcceptanceService(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        self::assertStringContainsString("if (\$command === 'mail:acceptance')", $source);
        self::assertStringContainsString('MailDeliveryAcceptance::class', $source);
        self::assertStringContainsString('--record=registration-verification|password-reset|email-change', $source);
    }
}
