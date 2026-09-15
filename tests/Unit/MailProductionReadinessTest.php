<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Mail\MailConfigurationCheck;
use NovaNuke\Core\Mail\MailProductionReadiness;
use PHPUnit\Framework\TestCase;

final class MailProductionReadinessTest extends TestCase
{
    public function testLogMailerRemainsStructurallyValidButIsNotProductionReady(): void
    {
        $config = new ConfigRepository([
            'mail' => [
                'mailer' => 'log',
                'from_address' => 'noreply@example.test',
                'from_name' => 'NovaNuke',
            ],
        ]);

        self::assertTrue((new MailConfigurationCheck($config))->passed());
        $readiness = new MailProductionReadiness($config, new MailConfigurationCheck($config));
        self::assertFalse($readiness->passed());

        $byName = [];
        foreach ($readiness->run() as $check) {
            $byName[$check['name']] = $check;
        }
        self::assertFalse($byName['Production mail transport']['passed']);
        self::assertTrue($byName['Production mail transport']['required']);
    }

    public function testStructurallyValidSmtpIsProductionReadyButDoesNotClaimDeliveryVerification(): void
    {
        $config = new ConfigRepository([
            'mail' => [
                'mailer' => 'smtp',
                'host' => 'smtp.example.test',
                'port' => 465,
                'username' => 'mailer@example.test',
                'password' => 'secret-value',
                'encryption' => 'ssl',
                'timeout' => 15,
                'from_address' => 'mailer@example.test',
                'from_name' => 'NovaNuke',
            ],
        ]);

        $readiness = new MailProductionReadiness($config, new MailConfigurationCheck($config));
        self::assertTrue($readiness->passed());
        $encoded = json_encode($readiness->run(), JSON_THROW_ON_ERROR);
        self::assertStringContainsString('Delivery is not yet considered verified', $encoded);
    }
}
