<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\System\DistributionSmokeCheck;
use PHPUnit\Framework\TestCase;

final class DistributionSmokeCheckTest extends TestCase
{
    public function testCurrentDistributionPassesSmokeContract(): void
    {
        $root = dirname(__DIR__, 2);
        $checks = (new DistributionSmokeCheck($root))->run();

        foreach ($checks as $check) {
            self::assertTrue($check['passed'], $check['name'] . ': ' . $check['detail']);
        }
    }

    public function testCliExposesSmokeCheckBeforeApplicationBootstrap(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/bin/cms');
        self::assertLessThan(
            strpos($source, '$application = require'),
            strpos($source, "if (\$command === 'release:smoke')"),
        );
        self::assertStringContainsString('DistributionSmokeCheck', $source);
    }
}
