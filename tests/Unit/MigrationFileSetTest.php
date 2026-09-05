<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Database\MigrationFileSet;
use PHPUnit\Framework\TestCase;

final class MigrationFileSetTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/novanuke-migrations-' . bin2hex(random_bytes(5));
        mkdir($this->directory, 0750, true);
        file_put_contents($this->directory . '/002_second.php', '<?php');
        file_put_contents($this->directory . '/001_first.php', '<?php');
        file_put_contents($this->directory . '/README.md', 'ignored');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) unlink($file);
        rmdir($this->directory);
    }

    public function testItReportsPendingAndMissingMigrationFilesDeterministically(): void
    {
        $status = (new MigrationFileSet())->compare($this->directory, ['001_first', '000_removed']);

        self::assertSame(2, $status['total']);
        self::assertSame(1, $status['executed']);
        self::assertSame(['002_second'], $status['pending']);
        self::assertSame(['000_removed'], $status['missing_files']);
    }

    public function testItTreatsAnAbsentDirectoryAsEmpty(): void
    {
        $status = (new MigrationFileSet())->compare($this->directory . '/missing', []);

        self::assertSame(['total' => 0, 'executed' => 0, 'pending' => [], 'missing_files' => []], $status);
    }
}
