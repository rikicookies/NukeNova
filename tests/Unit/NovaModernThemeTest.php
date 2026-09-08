<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class NovaModernThemeTest extends TestCase
{
    public function testAdministrativeLayoutRendersGroupedNavigationAndPageContent(): void
    {
        $root = dirname(__DIR__, 2);
        $twig = new Environment(new ChainLoader([
            new ArrayLoader(['admin-test.twig' => '{% extends "layouts/default.twig" %}{% block content %}<section id="admin-content">Editor</section>{% endblock %}']),
            new FilesystemLoader([$root . '/themes/novamodern', $root . '/resources/views']),
        ]), ['cache' => false, 'strict_variables' => true]);
        $twig->addFunction(new TwigFunction('trans', static fn (string $key): string => $key));

        $html = $twig->render('admin-test.twig', [
            'cms_locale' => 'en', 'cms_name' => 'NovaNuke', 'cms_description' => '',
            'theme' => ['asset_base' => '/assets/themes/novamodern', 'settings' => ['accent_color' => '#5b8def']],
            'admin_area' => true, 'admin_path' => '/admin/news/4/edit',
            'current_user' => ['username' => 'admin'],
            'admin_navigation' => [[
                'slug' => 'content', 'label' => 'Content', 'items' => [[
                    'label' => 'News', 'url' => '/admin/news', 'icon' => 'newspaper', 'active' => true,
                ]],
            ]],
        ]);

        self::assertStringContainsString('id="admin-content"', $html);
        self::assertStringContainsString('id="nova-admin-sidebar"', $html);
        self::assertStringContainsString('Content', $html);
        self::assertStringContainsString('/admin/news', $html);
        self::assertStringContainsString('#icon-newspaper', $html);
        self::assertStringNotContainsString('nova-public-main', $html);
    }

    public function testNavigationScriptPersistsTheDesktopStateAndSupportsEscape(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/themes/novamodern/assets/js/navigation.js');
        self::assertStringContainsString("localStorage.setItem('novamodern.navigation'", $script);
        self::assertStringContainsString("event.key === 'Escape'", $script);
        self::assertStringContainsString("setAttribute('aria-expanded'", $script);
    }

    public function testLightPaletteOverridesLegacyDarkContentSurfaces(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/themes/novamodern/assets/css/novamodern.css');
        self::assertStringContainsString('--nova-surface: #fff', $css);
        self::assertStringContainsString('--nova-text: #202634', $css);
        self::assertStringContainsString('.nova-modern :is(.welcome-card, .auth-card', $css);
        self::assertStringContainsString('.nova-modern .admin-table { background: var(--nova-surface)', $css);
        self::assertStringContainsString('.nova-content-grid { display: grid;', $css);
        self::assertStringContainsString('gap: .75rem', $css);
    }
}
