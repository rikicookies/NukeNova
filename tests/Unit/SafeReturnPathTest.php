<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Http\SafeReturnPath;
use PHPUnit\Framework\TestCase;

final class SafeReturnPathTest extends TestCase
{
    public function testItAcceptsOnlyAnExactAllowlistedPath(): void
    {
        self::assertSame('/news', SafeReturnPath::choose('/news', ['/news'], '/admin/news'));
        self::assertSame('/admin/news', SafeReturnPath::choose('https://evil.example', ['/news'], '/admin/news'));
        self::assertSame('/admin/news', SafeReturnPath::choose('//evil.example', ['/news'], '/admin/news'));
        self::assertSame('/admin/news', SafeReturnPath::choose('/news/../admin', ['/news'], '/admin/news'));
    }
}
