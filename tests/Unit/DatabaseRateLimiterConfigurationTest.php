<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Security\DatabaseRateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

final class DatabaseRateLimiterConfigurationTest extends TestCase
{
    public function testUnsafeConfigurationIsRejectedBeforeDatabaseUse(): void
    {
        $pdo=$this->createStub(PDO::class);
        $this->expectException(\InvalidArgumentException::class);
        new DatabaseRateLimiter($pdo,0,300,'login');
    }

    public function testOversizedOrControlCharacterKeysAreRejected(): void
    {
        $pdo=$this->createStub(PDO::class);
        $limiter=new DatabaseRateLimiter($pdo,5,300,'login');
        foreach([str_repeat('x',513),"bad\nkey"] as $key){
            try{$limiter->tooManyAttempts($key);self::fail('Unsafe key accepted.');}
            catch(\InvalidArgumentException){self::assertTrue(true);}
        }
    }
}
