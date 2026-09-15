<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Backup\BackupSetStatus;
use PHPUnit\Framework\TestCase;

final class BackupSetStatusTest extends TestCase
{
    public function testIncompleteDirectoryIsReportedWithoutBeingAccepted(): void
    {
        $root=sys_get_temp_dir().'/novanuke-backup-status-'.bin2hex(random_bytes(5));
        $set='.incomplete-set-20260914120000-0123456789abcdef01234567';
        mkdir($root.'/'.$set,0700,true);
        try {
            $items=(new BackupSetStatus($root))->inspect();
            self::assertCount(1,$items);
            self::assertSame('incomplete',$items[0]['status']);
            self::assertStringContainsString('manifest is missing',$items[0]['detail']);
        } finally { rmdir($root.'/'.$set); rmdir($root); }
    }
}
