<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Admin\DashboardHealthSummary;
use PHPUnit\Framework\TestCase;

final class DashboardHealthSummaryTest extends TestCase
{
    public function testItSummarizesAHealthySiteWithoutSensitiveDetails(): void
    {
        $summary = (new DashboardHealthSummary())->summarize([
            'maintenance' => false,
            'warnings' => [],
            'writable' => ['storage/cache' => true, 'storage/private' => true],
            'migrations' => ['pending_total' => 0, 'missing_total' => 0, 'module_updates_total' => 0],
        ]);

        self::assertSame('healthy', $summary['status']);
        self::assertSame(['Disabled', 'Up to date', 'Ready', 'Baseline checks passed'], array_column($summary['checks'], 'value'));
    }

    public function testItPrioritizesMissingMigrationsAndReportsOperationalWarnings(): void
    {
        $summary = (new DashboardHealthSummary())->summarize([
            'maintenance' => true,
            'warnings' => ['APP_DEBUG must be disabled.'],
            'writable' => ['storage/cache' => false, 'storage/private' => true],
            'migrations' => ['pending_total' => 2, 'missing_total' => 1, 'module_updates_total' => 3],
        ]);

        self::assertSame('attention', $summary['status']);
        self::assertSame(['warning', 'error', 'error', 'warning'], array_column($summary['checks'], 'status'));
        self::assertSame('1 missing migration file(s)', $summary['checks'][1]['value']);
        self::assertStringNotContainsString('APP_DEBUG', implode(' ', array_column($summary['checks'], 'value')));
    }

    public function testInterruptedMigrationIsAVisibleDatabaseError(): void
    {
        $summary = (new DashboardHealthSummary())->summarize([
            'maintenance' => true,
            'warnings' => ['An interrupted migration requires explicit recovery.'],
            'writable' => ['storage/cache' => true],
            'migrations' => ['pending_total' => 1, 'missing_total' => 0, 'recovery_total' => 1, 'module_updates_total' => 0],
        ]);

        self::assertSame('error', $summary['checks'][1]['status']);
        self::assertSame('1 migration recovery operation(s)', $summary['checks'][1]['value']);
    }
}
