<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Config\ConfigRepository;
use NovaNuke\Core\System\ProductionReadiness;
use PHPUnit\Framework\TestCase;

final class ProductionReadinessTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-production-' . bin2hex(random_bytes(5));
        foreach (['storage/cache', 'storage/logs', 'storage/sessions', 'storage/private', 'public/uploads'] as $directory) {
            mkdir($this->root . '/' . $directory, 0700, true);
        }
        file_put_contents($this->root . '/public/.htaccess', 'Options -Indexes');
        file_put_contents($this->root . '/public/uploads/.htaccess', 'Options -Indexes -ExecCGI');
        file_put_contents($this->root . '/storage/private/.htaccess', 'Require all denied');
        file_put_contents($this->root . '/.env', "APP_KEY=testing\n");
        @chmod($this->root . '/.env', 0600);
    }

    protected function tearDown(): void
    {
        @unlink($this->root . '/public/.htaccess');
        @unlink($this->root . '/public/uploads/.htaccess');
        @unlink($this->root . '/storage/private/.htaccess');
        @unlink($this->root . '/.env');
        foreach (['storage/cache', 'storage/logs', 'storage/sessions', 'storage/private', 'public/uploads'] as $directory) {
            @rmdir($this->root . '/' . $directory);
        }
        @rmdir($this->root . '/storage');
        @rmdir($this->root . '/public');
        @rmdir($this->root);
    }

    public function testItReportsUnsafeDeploymentConfiguration(): void
    {
        $config = new ConfigRepository([
            'app' => ['environment' => 'development', 'debug' => true, 'url' => 'http://example.test', 'key' => 'short'],
            'session' => ['secure' => false, 'same_site' => 'None', 'idle_timeout' => 60, 'rotation_interval' => 60],
            'security' => ['headers_enabled' => false],
            'mail' => ['mailer' => 'log'],
        ]);
        $checks = (new ProductionReadiness($config, $this->root))->run();
        $byName = [];
        foreach ($checks as $check) {
            $byName[$check['name']] = $check;
        }
        foreach ([
            'Production environment', 'Debug disabled', 'HTTPS URL', 'Secure session cookie',
            'Session SameSite policy', 'Session idle timeout', 'Session ID rotation', 'Security headers', 'Application key',
        ] as $name) {
            self::assertFalse($byName[$name]['passed'], $name);
        }
        self::assertFalse((new ProductionReadiness($config, $this->root))->passed());
    }


    public function testItRejectsOverlyPermissiveEnvironmentFileOnPosix(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('POSIX permission bits are not reliable on Windows.');
        }

        @chmod($this->root . '/.env', 0644);
        $config = new ConfigRepository([
            'app' => ['environment' => 'production', 'debug' => false, 'url' => 'https://example.test', 'key' => 'base64:' . str_repeat('a', 48)],
            'session' => ['secure' => true, 'same_site' => 'Lax', 'idle_timeout' => 1800, 'rotation_interval' => 900],
            'security' => ['headers_enabled' => true],
            'mail' => ['mailer' => 'smtp'],
        ]);
        $byName = [];
        foreach ((new ProductionReadiness($config, $this->root))->run() as $check) {
            $byName[$check['name']] = $check;
        }

        self::assertFalse($byName['Environment file permissions']['passed']);
        self::assertTrue($byName['Environment file permissions']['required']);
    }

    public function testItRequiresTheDistributedSharedHostingGuards(): void
    {
        unlink($this->root . '/public/uploads/.htaccess');
        $config = new ConfigRepository([
            'app' => ['environment' => 'production', 'debug' => false, 'url' => 'https://example.test', 'key' => 'base64:' . str_repeat('a', 48)],
            'session' => ['secure' => true, 'same_site' => 'Lax', 'idle_timeout' => 1800, 'rotation_interval' => 900],
            'security' => ['headers_enabled' => true],
            'mail' => ['mailer' => 'smtp'],
        ]);
        $byName = [];
        foreach ((new ProductionReadiness($config, $this->root))->run() as $check) {
            $byName[$check['name']] = $check;
        }
        self::assertFalse($byName['Shared-hosting Apache guards']['passed']);
        self::assertTrue($byName['Shared-hosting Apache guards']['required']);
    }
}
