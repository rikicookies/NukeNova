<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\ResetToken;
use PHPUnit\Framework\TestCase;

final class ResetTokenTest extends TestCase
{
    public function testItGeneratesRandomWellFormedTokens(): void
    {
        $first = ResetToken::generate();
        $second = ResetToken::generate();

        self::assertTrue(ResetToken::isWellFormed($first));
        self::assertNotSame($first, $second);
        self::assertSame(64, strlen(ResetToken::hash($first)));
    }

    public function testItRejectsMalformedTokens(): void
    {
        self::assertFalse(ResetToken::isWellFormed('short'));
        self::assertFalse(ResetToken::isWellFormed(str_repeat('z', 64)));
    }
}
