<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Config\ConfigRepository;
use PHPUnit\Framework\TestCase;

final class ConfigRepositoryTest extends TestCase
{
    public function testItReadsNestedValues(): void
    {
        $config = new ConfigRepository(['app' => ['name' => 'NovaNuke']]);

        self::assertSame('NovaNuke', $config->get('app.name'));
        self::assertSame('fallback', $config->get('app.missing', 'fallback'));
    }

    public function testRequiredValueThrowsWhenMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ConfigRepository())->require('app.key');
    }
}
