<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\Mail\MailConfigurationCheck;
use PHPUnit\Framework\TestCase;

final class MailConfigurationCheckTest extends TestCase
{
    public function testLogMailerIsValidForDevelopmentButLeavesSmtpAsWarning(): void
    {
        $check=new MailConfigurationCheck(new ConfigRepository([
            'mail'=>[
                'mailer'=>'log',
                'from_address'=>'noreply@example.test',
                'from_name'=>'NovaNuke',
            ],
        ]));

        self::assertTrue($check->passed());
        $byName=[];
        foreach($check->run() as $item) $byName[$item['name']]=$item;

        self::assertTrue($byName['Mail transport']['passed']);
        self::assertFalse($byName['SMTP configuration']['passed']);
        self::assertFalse($byName['SMTP configuration']['required']);
    }

    public function testValidSmtpConfigurationPassesWithoutSendingMail(): void
    {
        $check=new MailConfigurationCheck(new ConfigRepository([
            'mail'=>[
                'mailer'=>'smtp',
                'host'=>'smtp.example.test',
                'port'=>465,
                'username'=>'mailer@example.test',
                'password'=>'secret-value',
                'encryption'=>'ssl',
                'timeout'=>15,
                'from_address'=>'mailer@example.test',
                'from_name'=>'NovaNuke',
            ],
        ]));

        self::assertTrue($check->passed());
        $byName=[];
        foreach($check->run() as $item) $byName[$item['name']]=$item;
        self::assertTrue($byName['SMTP configuration']['passed']);
        self::assertTrue($byName['SMTP configuration']['required']);
    }

    public function testInvalidSmtpConfigurationFailsWithoutLeakingPassword(): void
    {
        $secret='do-not-leak-this';
        $check=new MailConfigurationCheck(new ConfigRepository([
            'mail'=>[
                'mailer'=>'smtp',
                'host'=>'bad host',
                'port'=>70000,
                'username'=>'mailer',
                'password'=>$secret,
                'encryption'=>'none',
                'timeout'=>1,
                'from_address'=>'invalid',
                'from_name'=>"Bad\r\nName",
            ],
        ]));

        self::assertFalse($check->passed());
        $encoded=json_encode($check->run(),JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($secret,$encoded);
    }
}
