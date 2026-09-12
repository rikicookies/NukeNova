<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ThrottleResponseContractTest extends TestCase
{
    public function testExplicit429AuthenticationResponsesIncludeRetryAfter(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'app/Auth/AuthController.php',
            'app/Auth/RegistrationController.php',
            'app/Auth/AccountController.php',
            'app/Auth/AccountSecurityController.php',
            'app/Auth/AccountEmailController.php',
        ] as $file) {
            $source = (string) file_get_contents($root . '/' . $file);
            self::assertStringContainsString("withHeader('Retry-After'", $source, $file);
        }
    }
}
