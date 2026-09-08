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
            'bio_format' => 'html',
            'website' => 'https://example.com/riki', 'location' => 'Los Angeles, CA',
            'locale' => 'es', 'timezone' => 'America/Los_Angeles', 'profile_visibility' => 'members',
        ]);
        self::assertSame([], $result['errors']);
        self::assertSame('Riki', $result['data']['display_name']);
        self::assertSame('<b>Pool builder</b>', $result['data']['bio']);
        self::assertSame('html', $result['data']['bio_format']);
        self::assertSame('https://example.com/riki', $result['data']['website']);
        self::assertSame('Los Angeles, CA', $result['data']['location']);
        self::assertSame(['profile_visibility' => 'members'], $result['data']['preferences']);
    }

    public function testItRejectsInvalidAndArrayInputs(): void
    {
        $result = (new ProfileInput())->validate([
            'display_name' => ['Riki'], 'bio' => str_repeat('x', 2001),
            'website' => 'javascript:alert(1)', 'location' => str_repeat('x', 121),
            'locale' => 'xx', 'timezone' => '../UTC', 'profile_visibility' => 'hidden',
        ]);
        foreach (['display_name', 'bio', 'website', 'location', 'locale', 'timezone', 'profile_visibility'] as $field) {
            self::assertArrayHasKey($field, $result['errors']);
        }
    }

    public function testBiographyUsesMarkdownByDefaultAndRejectsUnknownFormats(): void
    {
        $valid = ['display_name' => 'Riki', 'bio' => '**Builder**', 'locale' => 'en', 'timezone' => 'UTC', 'profile_visibility' => 'public'];
        $result = (new ProfileInput())->validate($valid);
        self::assertSame('markdown', $result['data']['bio_format']);
        self::assertSame([], $result['errors']);

        $invalid = (new ProfileInput())->validate($valid + ['bio_format' => 'php']);
        self::assertArrayHasKey('bio_format', $invalid['errors']);
    }
}
