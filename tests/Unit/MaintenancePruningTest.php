<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use InvalidArgumentException;
use NovaNuke\Core\Maintenance\MaintenancePruning;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MaintenancePruningTest extends TestCase
{
    public function testItAccumulatesAndSortsExtensionResults(): void
    {
        $event = new MaintenancePruning(true);
        $event->add('statistics.daily', 4);
        $event->add('search.queries', 2);
        $event->add('search.queries', 3);

        self::assertTrue($event->dryRun);
        self::assertSame([
            'search.queries' => 5,
            'statistics.daily' => 4,
        ], $event->results());
    }

    #[DataProvider('invalidResults')]
    public function testItRejectsInvalidResults(string $name, int $records): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new MaintenancePruning(false))->add($name, $records);
    }

    public static function invalidResults(): array
    {
        return [
            'unsafe name' => ['search queries', 1],
            'single-character name' => ['x', 1],
            'negative count' => ['search.queries', -1],
        ];
    }
}
