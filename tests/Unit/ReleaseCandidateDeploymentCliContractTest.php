<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReleaseCandidateDeploymentCliContractTest extends TestCase
{
    public function testRcDeploymentCommandUsesTheAggregatePreflight(): void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/bin/cms');
        $start=strpos($source,"if (\$command === 'rc:deployment')");
        $end=strpos($source,"if (\$command === 'production:check')",$start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $section=substr($source,$start,$end-$start);

        self::assertStringContainsString('ReleaseCandidateDeploymentCheck::class',$section);
        self::assertStringContainsString("'required'",$section);
        self::assertStringContainsString("'group'",$section);
    }
}
