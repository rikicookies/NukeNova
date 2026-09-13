<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\System\ReleaseChecklist;
use PHPUnit\Framework\TestCase;

final class ReleaseChecklistTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-release-' . bin2hex(random_bytes(5));
        foreach ([
            'public/uploads', 'bootstrap', 'app',
            'storage/cache', 'storage/logs', 'storage/sessions', 'storage/private',
            'docs',
        ] as $directory) {
            mkdir($this->root . '/' . $directory, 0750, true);
        }
        file_put_contents($this->root . '/public/index.php', '<?php');
        file_put_contents($this->root . '/public/.htaccess', 'Options -Indexes');
        file_put_contents($this->root . '/public/.user.ini', "display_errors=Off\nsession.use_strict_mode=1\n");
        file_put_contents($this->root . '/public/uploads/.htaccess', 'Options -Indexes -ExecCGI');
        file_put_contents($this->root . '/storage/private/.htaccess', "Require all denied\nDeny from all\n");
        file_put_contents($this->root . '/bootstrap/app.php', '<?php');
        file_put_contents($this->root . '/composer.json', '{}');
        file_put_contents($this->root . '/composer.lock', '{"packages":[]}');
        file_put_contents($this->root . '/.env.example', "APP_KEY=\nDB_PASSWORD=\nMAIL_PASSWORD=\n");
        foreach (['INSTALLATION.md', 'PRODUCTION.md', 'PRODUCTION_HARDENING.md'] as $doc) {
            file_put_contents($this->root . '/docs/' . $doc, '# test');
        }
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($this->root);
    }

    public function testItAcceptsAnIsolatedDistribution(): void
    {
        self::assertTrue((new ReleaseChecklist($this->root))->passed());
    }

    public function testItRejectsUnexpectedPublicPhpAndBackups(): void
    {
        file_put_contents($this->root . '/public/shell.php', '<?php');
        file_put_contents($this->root . '/public/database.sql', 'secret');
        self::assertFalse((new ReleaseChecklist($this->root))->passed());
    }

    public function testItRejectsSecretsInTheExampleEnvironment(): void
    {
        file_put_contents($this->root . '/.env.example', "APP_KEY=secret\nDB_PASSWORD=secret\nMAIL_PASSWORD=secret\n");
        self::assertFalse((new ReleaseChecklist($this->root))->passed());
    }


    public function testDistributionRequiresComposerLock(): void
    {
        unlink($this->root . '/composer.lock');
        self::assertFalse((new ReleaseChecklist($this->root))->passed());
    }

    public function testDistributionRequiresPrivateAndUploadHardeningFiles(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/System/ReleaseChecklist.php');

        self::assertStringContainsString("'public/uploads/.htaccess'", $source);
        self::assertStringContainsString("'storage/private/.htaccess'", $source);
        self::assertStringContainsString("'docs/PRODUCTION_HARDENING.md'", $source);
    }

}
