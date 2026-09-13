<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FileBackupRestoreCliContractTest extends TestCase
{
    public function testCliRequiresExplicitArchiveAndDestinationForFileRestore(): void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/bin/cms');
        self::assertStringContainsString('use NovaNuke\\Core\\Backup\\FileBackupRestorer;',$source);
        $start=strpos($source,"if (\$command === 'backup:restore-files')");
        $end=strpos($source,"if (\$command === 'module:inspect')",$start);
        $section=substr($source,$start,$end-$start);

        self::assertStringContainsString('--archive=PATH --destination=PATH',$section);
        self::assertStringContainsString('FileBackupRestorer',$section);
        self::assertStringContainsString("new BackupVerifier(\$rootPath.'/storage/private/backups')",$section);
        self::assertStringNotContainsString('--overwrite',$section);
        self::assertStringNotContainsString('--force',$section);
    }
}
