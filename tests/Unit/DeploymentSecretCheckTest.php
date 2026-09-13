<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\System\DeploymentSecretCheck;
use PHPUnit\Framework\TestCase;

final class DeploymentSecretCheckTest extends TestCase
{
    public function testProductionRejectsWeakKeyAndEmptyDatabasePassword(): void
    {
        $check=new DeploymentSecretCheck(new ConfigRepository([
            'app'=>['environment'=>'production','key'=>'base64:bad'],
            'database'=>['password'=>''],
            'mail'=>['mailer'=>'log','password'=>''],
        ]));

        self::assertFalse($check->passed());
        $byName=[];
        foreach($check->run() as $item) $byName[$item['name']]=$item;
        self::assertFalse($byName['Application key entropy']['passed']);
        self::assertFalse($byName['Database password']['passed']);
    }

    public function testSmtpRequiresPasswordWithoutDisclosingIt(): void
    {
        $check=new DeploymentSecretCheck(new ConfigRepository([
            'app'=>['environment'=>'production','key'=>'base64:'.base64_encode(random_bytes(32))],
            'database'=>['password'=>'db-secret'],
            'mail'=>['mailer'=>'smtp','password'=>''],
        ]));

        self::assertFalse($check->passed());
        self::assertStringNotContainsString('db-secret',json_encode($check->run(),JSON_THROW_ON_ERROR));
    }
}
