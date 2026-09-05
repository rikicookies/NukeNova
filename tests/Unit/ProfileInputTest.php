<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\ProfileInput;
use PHPUnit\Framework\TestCase;

final class ProfileInputTest extends TestCase
{
    public function testItNormalizesAValidProfile(): void
    {
        $result = (new ProfileInput())->validate([
            'display_name' => ' Riki ', 'bio' => ' <b>Pool builder</b> ',
            'locale' => 'es', 'timezone' => 'America/Los_Angeles', 'profile_visibility' => 'members',
        ]);
        self::assertSame([], $result['errors']);
        self::assertSame('Riki', $result['data']['display_name']);
        self::assertSame('Pool builder', $result['data']['bio']);
        self::assertSame(['profile_visibility' => 'members'], $result['data']['preferences']);
    }

    public function testItRejectsInvalidAndArrayInputs(): void
    {
        $result = (new ProfileInput())->validate([
            'display_name' => ['Riki'], 'bio' => str_repeat('x', 2001),
            'locale' => 'xx', 'timezone' => '../UTC', 'profile_visibility' => 'hidden',
        ]);
        foreach (['display_name', 'bio', 'locale', 'timezone', 'profile_visibility'] as $field) {
            self::assertArrayHasKey($field, $result['errors']);
        }
    }
}
