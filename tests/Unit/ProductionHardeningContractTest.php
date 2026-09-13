<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductionHardeningContractTest extends TestCase
{
    public function testSensitiveSurfacesDefaultToNoStoreCaching(): void
    {
        $kernel = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/Http/Kernel.php');

        foreach (['/admin', '/account', '/login', '/register', '/password'] as $prefix) {
            self::assertStringContainsString("str_starts_with(\$path, '{$prefix}')", $kernel);
        }
        self::assertStringContainsString("'Cache-Control', 'no-store, private'", $kernel);
        self::assertStringContainsString('$response->status() >= 400', $kernel);
    }

    public function testSharedHostingGuardsBlockTraceAndExecutableUploads(): void
    {
        $root = dirname(__DIR__, 2);
        $public = (string) file_get_contents($root . '/public/.htaccess');
        $uploads = (string) file_get_contents($root . '/public/uploads/.htaccess');

        self::assertStringContainsString('REQUEST_METHOD} ^TRACE$', $public);
        self::assertStringContainsString('Options -Indexes -ExecCGI', $uploads);
        foreach (['php', 'phtml', 'phar', 'cgi', 'sh', 'exe', 'ps1'] as $extension) {
            self::assertStringContainsString($extension, $uploads);
        }
        self::assertStringContainsString('X-Content-Type-Options', $uploads);
        self::assertStringContainsString('Cross-Origin-Resource-Policy', $uploads);
    }

    public function testBetaOneMigrationContractDoesNotRejectLaterBetaTwoMigrations(): void
    {
        $root = dirname(__DIR__, 2);
        $migrationNames = array_map('basename', glob($root . '/database/migrations/*.php') ?: []);

        foreach ($migrationNames as $name) {
            self::assertStringNotContainsString('beta', strtolower($name));
        }

        self::assertContains('2026_09_08_000015_create_user_entitlements.php', $migrationNames);
        self::assertContains('2026_09_10_000018_add_membership_metadata.php', $migrationNames);
    }}
