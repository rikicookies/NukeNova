<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Settings\GeneralSettingsInput;
use PHPUnit\Framework\TestCase;

final class GeneralSettingsInputTest extends TestCase
{
    private GeneralSettingsInput $validator;

    protected function setUp(): void
    {
        $this->validator = new GeneralSettingsInput();
    }

    public function testItNormalizesAValidConfiguration(): void
    {
        $result = $this->validator->validate($this->validInput([
            'description' => ' <b>Community portal</b> ',
            'url' => 'https://example.com/community/',
            'admin_email' => 'ADMIN@EXAMPLE.COM',
            'maintenance' => '1',
        ]), ['home' => 'Welcome', 'news' => 'News']);

        self::assertSame([], $result['errors']);
        self::assertSame('Community portal', $result['data']['description']);
        self::assertSame('https://example.com/community', $result['data']['url']);
        self::assertSame('admin@example.com', $result['data']['admin_email']);
        self::assertSame(10, $result['data']['per_page']);
        self::assertTrue($result['data']['maintenance']);
    }

    public function testItRejectsUnsafeOrAmbiguousSiteUrls(): void
    {
        foreach (['javascript:alert(1)', 'https://user@example.com', 'https://example.com?a=1', 'https://example.com/#part'] as $url) {
            $result = $this->validator->validate($this->validInput(['url' => $url]), ['home' => 'Welcome']);
            self::assertArrayHasKey('url', $result['errors'], $url);
        }
    }

    public function testItRejectsUnknownLocalesTimezonesAndDateFormats(): void
    {
        $result = $this->validator->validate($this->validInput([
            'locale' => 'xx', 'timezone' => 'Mars/Olympus', 'date_format' => 'r',
        ]), ['home' => 'Welcome']);

        self::assertArrayHasKey('locale', $result['errors']);
        self::assertArrayHasKey('timezone', $result['errors']);
        self::assertArrayHasKey('date_format', $result['errors']);
    }

    public function testItRejectsPaginationOutsideTheSafeRange(): void
    {
        foreach (['4', '101', 'ten'] as $perPage) {
            $result = $this->validator->validate($this->validInput(['per_page' => $perPage]), ['home' => 'Welcome']);
            self::assertArrayHasKey('per_page', $result['errors']);
        }
    }

    public function testHomepageMustBelongToAnEnabledOption(): void
    {
        $result = $this->validator->validate($this->validInput(['homepage' => 'news']), ['home' => 'Welcome']);
        self::assertArrayHasKey('homepage', $result['errors']);
    }

    public function testArrayInputsAreRejectedWithoutBeingStringified(): void
    {
        $result = $this->validator->validate($this->validInput([
            'name' => ['NovaNuke'], 'url' => ['https://example.com'], 'per_page' => ['10'],
        ]), ['home' => 'Welcome']);

        self::assertArrayHasKey('name', $result['errors']);
        self::assertArrayHasKey('url', $result['errors']);
        self::assertArrayHasKey('per_page', $result['errors']);
    }

    /** @param array<string,mixed> $changes @return array<string,mixed> */
    private function validInput(array $changes = []): array
    {
        return array_replace([
            'name' => 'NovaNuke',
            'description' => 'Community portal',
            'url' => 'https://example.com',
            'admin_email' => 'admin@example.com',
            'timezone' => 'UTC',
            'locale' => 'en',
            'date_format' => 'F j, Y',
            'per_page' => '10',
            'homepage' => 'home',
            'maintenance' => '0',
        ], $changes);
    }
}
