<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PublicProfileContentTest extends TestCase
{
    public function testPublicBiographyUsesTheRestrictedRenderer(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root . '/app/Auth/PublicProfileController.php');
        $template = (string) file_get_contents($root . '/resources/views/auth/profile-public.twig');
        self::assertStringContainsString('ContentProfile::Profile', $controller);
        self::assertStringContainsString("profile['bio_html']", $controller);
        self::assertStringContainsString('profile.bio_html', $template);
        self::assertStringNotContainsString('profile.bio|nl2br', $template);
        self::assertStringContainsString('rel="noopener noreferrer"', $template);
        self::assertStringContainsString('profile.location', $template);
    }
}
