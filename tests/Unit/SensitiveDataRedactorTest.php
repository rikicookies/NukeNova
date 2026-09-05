<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Logging\SensitiveDataRedactor;
use PHPUnit\Framework\TestCase;

final class SensitiveDataRedactorTest extends TestCase
{
    public function testItRemovesCommonCredentialAndTokenForms(): void
    {
        $redacted = (new SensitiveDataRedactor())->redact(
            'password=hunter2 token:abcdef Authorization: Bearer abc.def.ghi https://user:pass@example.test',
        );
        self::assertStringNotContainsString('hunter2', $redacted);
        self::assertStringNotContainsString('abcdef', $redacted);
        self::assertStringNotContainsString('abc.def.ghi', $redacted);
        self::assertStringNotContainsString(':pass@', $redacted);
    }

    public function testItRemovesLineBreaksFromLogMessages(): void
    {
        self::assertSame('first second', (new SensitiveDataRedactor())->redact("first\r\nsecond"));
        self::assertSame('first second', (new SensitiveDataRedactor())->redact("first\n\r\nsecond"));
    }
}
