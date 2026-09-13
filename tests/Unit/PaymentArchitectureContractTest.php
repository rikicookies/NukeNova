<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PaymentArchitectureContractTest extends TestCase
{
    public function testPaymentsAreOptionalAndProviderVerified(): void
    {
        $root=dirname(__DIR__,2);
        $application=(string)file_get_contents($root.'/app/Core/Application.php');
        $provider=(string)file_get_contents($root.'/app/Core/Billing/PaymentProviderInterface.php');
        $provisioner=(string)file_get_contents($root.'/app/Core/Billing/MembershipPaymentProvisioner.php');

        self::assertStringContainsString('new PaymentProviderRegistry()',$application);
        self::assertStringNotContainsString('Stripe',$application);
        self::assertStringNotContainsString('PayPal',$application);
        self::assertStringContainsString('function verify(string $payload',$provider);
        self::assertStringContainsString('->verify($payload,$headers)',$provisioner);
        self::assertStringNotContainsString("paid=true",$provisioner);
    }

    public function testPaymentProvisioningHasDurableIdempotencyAndNoCardStorage(): void
    {
        $root=dirname(__DIR__,2);
        $migration=(string)file_get_contents($root.'/database/migrations/2026_09_10_000022_create_payment_receipts.php');
        $provisioner=(string)file_get_contents($root.'/app/Core/Billing/MembershipPaymentProvisioner.php');

        self::assertStringContainsString('UNIQUE KEY payment_receipts_provider_reference_unique',$migration);
        self::assertStringContainsString('provider,external_reference',$migration);
        self::assertStringNotContainsString('card_number',strtolower($migration));
        self::assertStringNotContainsString('cvv',strtolower($migration));
        self::assertStringNotContainsString('secret',strtolower($migration));
        self::assertStringContainsString("'payment'",$provisioner);
    }

    public function testPaymentProvisioningUsesMembershipContractRatherThanEntitlementSql(): void
    {
        $root=dirname(__DIR__,2);
        $provisioner=(string)file_get_contents($root.'/app/Core/Billing/MembershipPaymentProvisioner.php');

        self::assertStringContainsString('MembershipProvisionerInterface',$provisioner);
        self::assertStringContainsString('->provision(',$provisioner);
        self::assertStringNotContainsString('user_entitlements',$provisioner);
        self::assertStringNotContainsString('EntitlementService',$provisioner);
    }
    public function testCoreDoesNotExposeABuiltInCheckoutOrProvider(): void
    {
        $root=dirname(__DIR__,2);
        $routes='';
        foreach(glob($root.'/routes/*.php')?:[] as $file) $routes.=(string)file_get_contents($file);

        self::assertStringNotContainsString('/checkout',$routes);
        self::assertStringNotContainsString('/payment/webhook',$routes);
        self::assertFileExists($root.'/docs/PAYMENTS.md');

        $docs=(string)file_get_contents($root.'/docs/PAYMENTS.md');
        self::assertStringContainsString('no checkout route',$docs);
        self::assertStringContainsString('no bundled Stripe/PayPal/etc. provider',$docs);
    }

    public function testDuplicateKeyRaceIsResolvedThroughCanonicalReceipt(): void
    {
        $root=dirname(__DIR__,2);
        $repository=(string)file_get_contents($root.'/app/Core/Billing/PaymentReceiptRepository.php');
        $provisioner=(string)file_get_contents($root.'/app/Core/Billing/MembershipPaymentProvisioner.php');

        self::assertStringContainsString('driverCode===1062',$repository);
        self::assertStringContainsString('DuplicatePaymentReceipt',$repository);
        self::assertStringContainsString('catch(DuplicatePaymentReceipt)',$provisioner);
        self::assertStringContainsString('could not be reloaded',$provisioner);
    }


}
