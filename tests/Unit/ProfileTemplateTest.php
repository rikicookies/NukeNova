<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class ProfileTemplateTest extends TestCase
{
    public function testEditTemplateAcceptsAnEmptyProfileInStrictMode(): void
    {
        $root = dirname(__DIR__, 2);
        $twig = new Environment(new FilesystemLoader($root . '/resources/views'), [
            'cache' => false,
            'strict_variables' => true,
        ]);
        $twig->addFunction(new TwigFunction('trans', static fn (string $key): string => $key));

        $html = $twig->render('auth/profile-edit.twig', [
            'profile' => [],
            'errors' => [],
            'message' => null,
            'avatar_error' => null,
            'password_error' => null,
            'csrf_token' => 'test-token',
            'timezones' => ['UTC'],
            'cms_locales' => ['en' => 'English'],
            'cms_locale' => 'en',
            'cms_name' => 'NovaNuke',
            'cms_description' => '',
            'current_user' => ['id' => 1],
            'blocks' => [],
            'menus' => [],
        ]);

        self::assertStringContainsString('name="display_name" value=""', $html);
        self::assertStringContainsString('value="test-token"', $html);
    }

    public function testGeneralSettingsTemplateAcceptsAnEmptyErrorMapInStrictMode(): void
    {
        $root = dirname(__DIR__, 2);
        $twig = new Environment(new FilesystemLoader($root . '/resources/views'), [
            'cache' => false,
            'strict_variables' => true,
        ]);
        $twig->addFunction(new TwigFunction('trans', static fn (string $key): string => $key));

        $html = $twig->render('admin/general-settings.twig', [
            'values' => [],
            'errors' => [],
            'saved' => false,
            'csrf_token' => 'settings-token',
            'timezones' => ['UTC'],
            'date_formats' => ['F j, Y' => 'September 4, 2026'],
            'homepages' => ['home' => 'Home'],
            'cms_locales' => ['en' => 'English'],
            'cms_locale' => 'en',
            'cms_name' => 'NovaNuke',
            'cms_description' => '',
            'current_user' => ['id' => 1],
            'blocks' => [],
            'menus' => [],
        ]);

        self::assertStringContainsString('name="name" value=""', $html);
        self::assertStringContainsString('value="settings-token"', $html);
    }
}
