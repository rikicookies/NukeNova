<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class ThemeBlockRegionTest extends TestCase
{
    public function testDefaultLayoutsRenderSidebarsAroundEveryChildPage(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['default', 'classic'] as $theme) {
            $loader = new ChainLoader([
                new ArrayLoader(['test-page.twig' => '{% extends "layouts/default.twig" %}{% block content %}<article id="route-content">Route content</article>{% endblock %}']),
                new FilesystemLoader([$root . '/themes/' . $theme, $root . '/resources/views']),
            ]);
            $twig = new Environment($loader, ['cache' => false, 'strict_variables' => true]);
            $twig->addFunction(new TwigFunction('trans', static fn (string $key, array $parameters = []): string => $key));
            $block = static fn (string $slug, string $title): array => [
                'slug' => $slug, 'title' => $title, 'show_title' => true, 'html' => 'Dynamic body',
            ];

            $html = $twig->render('test-page.twig', [
                'cms_locale' => 'en', 'cms_name' => 'NovaNuke', 'cms_description' => '',
                'theme' => ['asset_base' => '/assets/themes/' . $theme, 'settings' => [
                    'accent_color' => '#ffffff', 'site_tagline' => 'Test tagline',
                ]],
                'blocks' => [
                    'header' => [], 'before-content' => [], 'after-content' => [], 'footer' => [],
                    'left-sidebar' => [$block('left-test', 'Left test')],
                    'right-sidebar' => [$block('right-test', 'Right test')],
                ],
                'menus' => ['primary' => []], 'current_user' => null,
            ]);

            self::assertStringContainsString('id="route-content"', $html, $theme);
            self::assertStringContainsString('data-block-position="left-sidebar"', $html, $theme);
            self::assertStringContainsString('data-block-position="right-sidebar"', $html, $theme);
            self::assertSame(1, substr_count($html, 'Left test'), $theme);
            self::assertSame(1, substr_count($html, 'Right test'), $theme);
        }
    }
}
