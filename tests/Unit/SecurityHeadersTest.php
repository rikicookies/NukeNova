<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\Response;
use NovaNuke\Core\Security\SecurityHeaders;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function testItAddsProductionSafeDefaultsWithoutReplacingExplicitHeaders(): void
    {
        $headers = new SecurityHeaders(true, false, 31536000, 'http://localhost', 'development');
        $response = $headers->apply(Response::html('ok')->withHeader('Referrer-Policy', 'no-referrer'));

        self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
        self::assertSame('no-referrer', $response->header('Referrer-Policy'));
        self::assertNotNull($response->header('Content-Security-Policy'));
        self::assertSame('same-origin', $response->header('Cross-Origin-Opener-Policy'));
        self::assertSame('same-origin', $response->header('Cross-Origin-Resource-Policy'));
        self::assertSame('none', $response->header('X-Permitted-Cross-Domain-Policies'));
        self::assertNull($response->header('Strict-Transport-Security'));
    }

    public function testHstsRequiresExplicitProductionHttpsConfiguration(): void
    {
        $headers = new SecurityHeaders(true, true, 31536000, 'https://example.test', 'production');
        $response = $headers->apply(Response::html('ok'));

        self::assertSame('max-age=31536000; includeSubDomains', $response->header('Strict-Transport-Security'));
    }

    public function testHeadersCanBeDisabled(): void
    {
        $response = (new SecurityHeaders(false, true, 31536000, 'https://example.test', 'production'))
            ->apply(Response::html('ok'));

        self::assertNull($response->header('Content-Security-Policy'));
    }

    public function testProductionHttpsCspUpgradesInsecureRequests(): void
    {
        $response=(new SecurityHeaders(true,false,31536000,'https://example.test','production'))
            ->apply(Response::html('ok'));

        self::assertStringContainsString('upgrade-insecure-requests',(string)$response->header('Content-Security-Policy'));
    }

}
