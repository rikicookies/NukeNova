<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Billing\PaymentProviderInterface;
use NovaNuke\Core\Billing\PaymentProviderRegistry;
use NovaNuke\Core\Billing\VerifiedPayment;
use PHPUnit\Framework\TestCase;

final class PaymentFoundationTest extends TestCase
{
    public function testRegistryIsEmptyByDefaultAndRejectsDuplicateProviders(): void
    {
        $registry=new PaymentProviderRegistry();
        self::assertSame([],$registry->keys());
        self::assertFalse($registry->has('fake'));

        $provider=new class implements PaymentProviderInterface {
            public function key(): string{return 'fake';}
            public function verify(string $payload,array $headers=[]): VerifiedPayment
            {
                return new VerifiedPayment('fake','ref-1',1,'vip-30',1000,'USD');
            }
        };
        $registry->register($provider);

        self::assertTrue($registry->has('fake'));
        self::assertSame(['fake'],$registry->keys());
        self::assertSame($provider,$registry->get('fake'));

        $this->expectException(InvalidArgumentException::class);
        $registry->register($provider);
    }

    public function testVerifiedPaymentRejectsUntrustedShapes(): void
    {
        foreach([
            static fn()=>new VerifiedPayment('Bad Provider','ref',1,'vip-30',100,'USD'),
            static fn()=>new VerifiedPayment('fake','',1,'vip-30',100,'USD'),
            static fn()=>new VerifiedPayment('fake','ref',0,'vip-30',100,'USD'),
            static fn()=>new VerifiedPayment('fake','ref',1,'VIP 30',100,'USD'),
            static fn()=>new VerifiedPayment('fake','ref',1,'vip-30',-1,'USD'),
            static fn()=>new VerifiedPayment('fake','ref',1,'vip-30',100,'usd'),
        ] as $factory){
            try{
                $factory();
                self::fail('Invalid verified payment shape should fail.');
            }catch(InvalidArgumentException){
                self::assertTrue(true);
            }
        }
    }
}
