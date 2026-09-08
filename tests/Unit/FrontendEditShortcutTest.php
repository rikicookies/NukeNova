<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class FrontendEditShortcutTest extends TestCase
{
    public function testSharedActionHidesAndShowsTheEditShortcut(): void
    {
        $root = dirname(__DIR__, 2);
        $twig = new Environment(new FilesystemLoader($root . '/resources/views'), [
            'cache' => false,
            'strict_variables' => true,
        ]);

        self::assertSame('', trim($twig->render('components/content-actions.twig', ['edit_url' => null])));
        $html = $twig->render('components/content-actions.twig', ['edit_url' => '/admin/news/42/edit']);
        self::assertStringContainsString('href="/admin/news/42/edit"', $html);
        self::assertStringContainsString('>Edit</a>', $html);
    }

    public function testEveryPublicDetailUsesItsExistingAuthorizedEditor(): void
    {
        $root = dirname(__DIR__, 2);
        $contracts = [
            'modules/News/src/PublicNewsController.php' => ['news.edit', '/admin/news/%d/edit'],
            'modules/Pages/src/PublicPagesController.php' => ['pages.edit', '/admin/pages/'],
            'modules/Downloads/src/PublicDownloadsController.php' => ['downloads.manage', '/admin/downloads/'],
            'modules/WebLinks/src/PublicWebLinksController.php' => ['web-links.manage', '/admin/web-links/'],
        ];
        foreach ($contracts as $relative => $expected) {
            $source = (string) file_get_contents($root . '/' . $relative);
            self::assertStringContainsString('AuthorizationService', $source, $relative);
            self::assertStringContainsString($expected[0], $source, $relative);
            self::assertStringContainsString($expected[1], $source, $relative);
            self::assertStringContainsString('edit_url', $source, $relative);
        }

        foreach (['News/views/show.twig', 'Pages/views/default.twig', 'Pages/views/landing.twig', 'Downloads/views/show.twig', 'WebLinks/views/show.twig'] as $relative) {
            self::assertStringContainsString(
                "components/content-actions.twig",
                (string) file_get_contents($root . '/modules/' . $relative),
                $relative,
            );
        }
    }

    public function testSharedActionRendersAConfirmedCsrfProtectedDeleteForm(): void
    {
        $root = dirname(__DIR__, 2);
        $twig = new Environment(new FilesystemLoader($root . '/resources/views'), ['cache' => false, 'strict_variables' => true]);
        $html = $twig->render('components/content-actions.twig', [
            'edit_url' => null,
            'delete_url' => '/admin/news/42/delete',
            'csrf_token' => 'safe-token',
            'return_to' => '/news',
        ]);

        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/admin/news/42/delete"', $html);
        self::assertStringContainsString('name="_token" value="safe-token"', $html);
        self::assertStringContainsString('name="confirm_delete" value="1" required', $html);
        self::assertStringContainsString('name="return_to" value="/news"', $html);
    }

    public function testDeleteControllersUseExactPublicReturnAllowLists(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'modules/News/src/AdminNewsController.php' => "['/news']",
            'modules/Pages/src/AdminPagesController.php' => "['/pages']",
            'modules/Downloads/src/AdminDownloadsController.php' => "['/downloads']",
            'modules/WebLinks/src/AdminWebLinksController.php' => "['/links']",
        ] as $relative => $allowList) {
            $source = (string) file_get_contents($root . '/' . $relative);
            self::assertStringContainsString('SafeReturnPath::choose', $source, $relative);
            self::assertStringContainsString($allowList, $source, $relative);
        }
    }
}
