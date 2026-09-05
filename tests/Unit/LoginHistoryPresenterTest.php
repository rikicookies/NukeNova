<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\LoginHistoryPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoginHistoryPresenterTest extends TestCase
{
    #[DataProvider('agents')]
    public function testItCreatesAConservativeDeviceLabel(string $agent, string $expected): void
    {
        self::assertSame($expected, (new LoginHistoryPresenter())->device($agent));
    }

    /** @return iterable<string,array{string,string}> */
    public static function agents(): iterable
    {
        yield 'edge windows' => ['Mozilla/5.0 (Windows NT 10.0) Edg/140.0', 'Edge on Windows'];
        yield 'safari iphone' => ['Mozilla/5.0 (iPhone) AppleWebKit/605.1 Safari/604.1', 'Safari on iOS'];
        yield 'unknown' => ['', 'Unknown browser on Unknown device'];
    }
}
