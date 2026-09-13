<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Integration;

use NovaNuke\Core\Access\EntitlementService;
use NovaNuke\Core\Billing\MembershipPaymentProvisioner;
use NovaNuke\Core\Billing\DuplicatePaymentReceipt;
use NovaNuke\Core\Billing\PaymentProviderInterface;
use NovaNuke\Core\Billing\PaymentProviderRegistry;
use NovaNuke\Core\Billing\PaymentHealthCheck;
use NovaNuke\Core\Billing\PaymentReceiptRepository;
use NovaNuke\Core\Billing\VerifiedPayment;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Membership\MembershipPlanCatalog;
use NovaNuke\Core\Membership\MembershipService;
use NovaNuke\Tests\Integration\Support\MySqlIntegrationTestCase;
use RuntimeException;

final class PaymentProvisioningIntegrationTest extends MySqlIntegrationTestCase
{
    public function testVerifiedPaymentProvisionsVipExactlyOnce(): void
    {
        $user=$this->user('paid-once');
        $provisioner=$this->provisioner(new VerifiedPayment('fake','checkout-100',$user,'vip-30',2500,'USD'));

        $first=$provisioner->handle('fake','signed-payload',['x-signature'=>'valid']);
        self::assertFalse($first['duplicate']);
        self::assertTrue($first['membership']['vip']);
        self::assertSame('vip-30',$first['membership']['plan_key']);

        $second=$provisioner->handle('fake','signed-payload',['x-signature'=>'valid']);
        self::assertTrue($second['duplicate']);

        $count=$this->db()->prepare("SELECT COUNT(*) FROM user_entitlements WHERE user_id=:user AND source='payment'");
        $count->execute(['user'=>$user]);
        self::assertSame(1,(int)$count->fetchColumn());

        $receipts=$this->db()->prepare('SELECT COUNT(*) FROM payment_receipts WHERE user_id=:user');
        $receipts->execute(['user'=>$user]);
        self::assertSame(1,(int)$receipts->fetchColumn());
    }

    public function testReusedReferenceWithDifferentPaymentDataIsRejected(): void
    {
        $user=$this->user('paid-tamper');
        $registry=new PaymentProviderRegistry();
        $current=new VerifiedPayment('fake','checkout-200',$user,'vip-30',2500,'USD');
        $provider=new MutableFakePaymentProvider($current);
        $registry->register($provider);
        $service=$this->membership();
        $provisioner=new MembershipPaymentProvisioner(
            $this->db(),$registry,new PaymentReceiptRepository($this->db()),$service
        );

        $provisioner->handle('fake','payload');
        $provider->payment=new VerifiedPayment('fake','checkout-200',$user,'vip-90',2500,'USD');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already used with different payment data');
        $provisioner->handle('fake','payload');
    }

    public function testFailedMembershipProvisioningRollsBackReceipt(): void
    {
        $user=$this->user('paid-rollback');
        $payment=new VerifiedPayment('fake','checkout-300',$user,'free',0,'USD');
        $provisioner=$this->provisioner($payment);

        try{
            $provisioner->handle('fake','payload');
            self::fail('Free payment provisioning should fail.');
        }catch(\InvalidArgumentException){
            self::assertTrue(true);
        }

        $statement=$this->db()->prepare('SELECT COUNT(*) FROM payment_receipts WHERE external_reference=:reference');
        $statement->execute(['reference'=>'checkout-300']);
        self::assertSame(0,(int)$statement->fetchColumn());
    }

    public function testProviderMismatchIsRejectedBeforeReceiptOrMembershipMutation(): void
    {
        $user=$this->user('paid-provider-mismatch');
        $registry=new PaymentProviderRegistry();
        $registry->register(new class($user) implements PaymentProviderInterface {
            public function __construct(private readonly int $user){}
            public function key(): string{return 'fake';}
            public function verify(string $payload,array $headers=[]): VerifiedPayment
            {
                return new VerifiedPayment('other','checkout-400',$this->user,'vip-30',2500,'USD');
            }
        });
        $provisioner=new MembershipPaymentProvisioner(
            $this->db(),$registry,new PaymentReceiptRepository($this->db()),$this->membership()
        );

        try{
            $provisioner->handle('fake','payload');
            self::fail('Provider mismatch should fail.');
        }catch(RuntimeException $error){
            self::assertStringContainsString('does not match',$error->getMessage());
        }

        self::assertSame(0,(int)$this->db()->query('SELECT COUNT(*) FROM payment_receipts')->fetchColumn());
        self::assertFalse($this->membership()->isVip($user));
    }



    public function testReceiptRepositoryMapsDuplicateKeyToDomainException(): void
    {
        $user=$this->user('paid-duplicate-repository');
        $payment=new VerifiedPayment('fake','checkout-duplicate',$user,'vip-30',2500,'USD');
        $repository=new PaymentReceiptRepository($this->db());

        $repository->record($payment);

        $this->expectException(DuplicatePaymentReceipt::class);
        $repository->record($payment);
    }

    public function testPaymentHealthCheckAcceptsNormalReceiptState(): void
    {
        $user=$this->user('paid-health');
        $registry=new PaymentProviderRegistry();
        $payment=new VerifiedPayment('fake','checkout-health',$user,'vip-90',5000,'USD');
        $registry->register(new MutableFakePaymentProvider($payment));
        $membership=$this->membership();
        $provisioner=new MembershipPaymentProvisioner(
            $this->db(),$registry,new PaymentReceiptRepository($this->db()),$membership
        );

        $provisioner->handle('fake','payload');

        $checks=(new PaymentHealthCheck($this->db(),new MembershipPlanCatalog(),$registry))->run();
        foreach($checks as $check){
            self::assertTrue($check['passed'],$check['name'].': '.$check['detail']);
        }
    }

    private function provisioner(VerifiedPayment $payment): MembershipPaymentProvisioner
    {
        $registry=new PaymentProviderRegistry();
        $registry->register(new MutableFakePaymentProvider($payment));
        return new MembershipPaymentProvisioner(
            $this->db(),$registry,new PaymentReceiptRepository($this->db()),$this->membership()
        );
    }

    private function membership(): MembershipService
    {
        return new MembershipService(
            new EntitlementService($this->db()),
            new MembershipPlanCatalog(),
            new EventDispatcher(),
        );
    }

    private function user(string $username): int
    {
        $statement=$this->db()->prepare(
            'INSERT INTO users (username,email,password_hash,status,email_verified_at,created_at,updated_at) '
            . 'VALUES (:username,:email,:password,\'active\',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())'
        );
        $statement->execute([
            'username'=>$username,
            'email'=>$username.'@example.test',
            'password'=>password_hash('Integration-Password-92!',PASSWORD_DEFAULT),
        ]);
        return (int)$this->db()->lastInsertId();
    }
}

final class MutableFakePaymentProvider implements PaymentProviderInterface
{
    public function __construct(public VerifiedPayment $payment){}

    public function key(): string{return 'fake';}

    public function verify(string $payload,array $headers=[]): VerifiedPayment
    {
        if(($headers['x-signature']??'valid')!=='valid') throw new RuntimeException('Invalid fake signature.');
        return $this->payment;
    }
}
