<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Update\UpgradeCompletion;
use PHPUnit\Framework\TestCase;

final class UpgradeCompletionTest extends TestCase
{
    public function testItAcceptsACleanCompletionFromTheRecordedSource(): void
    {
        $checks = (new UpgradeCompletion())->check(
            '0.4.0-beta.9',
            '0.4.0-beta.10',
            '0.4.0-beta.9',
            $this->upgradeStatus(),
            true,
        );

        foreach ($checks as $check) {
            if ($check['required']) self::assertTrue($check['passed'], $check['name']);
        }
    }

    public function testItBlocksMismatchedVersionAndIncompleteDatabaseState(): void
    {
        $status = $this->upgradeStatus();
        $status['pending_total'] = 1;
        $status['module_updates_total'] = 2;
        $checks = $this->byName((new UpgradeCompletion())->check(
            '0.4.0-beta.9',
            '0.4.0-beta.10',
            '0.2.0-alpha.48',
            $status,
            false,
        ));

        foreach (['Recorded source version', 'Core and module migrations', 'Module versions', 'Distribution release check'] as $name) {
            self::assertTrue($checks[$name]['required']);
            self::assertFalse($checks[$name]['passed']);
        }
    }

    public function testLegacyInstallationCanInitializeItsRecordedVersionWithAWarning(): void
    {
        $check = $this->byName((new UpgradeCompletion())->check(
            '0.4.0-beta.9',
            '0.4.0-beta.10',
            null,
            $this->upgradeStatus(),
            true,
        ))['Recorded source version'];

        self::assertFalse($check['required']);
        self::assertFalse($check['passed']);
        self::assertStringContainsString('initialize', $check['detail']);
    }

    public function testFreshInstallerRecordsTheCurrentCoreVersion(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Installer/InstallerService.php');
        self::assertStringContainsString("['system.core_version', Version::CURRENT", $source);
        self::assertStringContainsString("['system.core_updated_at', gmdate(DATE_ATOM)", $source);
    }

    /** @return array<string,int> */
    private function upgradeStatus(): array
    {
        return ['pending_total' => 0, 'missing_total' => 0, 'module_updates_total' => 0];
    }

    /** @return array<string,array{name:string,passed:bool,required:bool,detail:string}> */
    private function byName(array $checks): array
    {
        $indexed = [];
        foreach ($checks as $check) $indexed[$check['name']] = $check;
        return $indexed;
    }
}
