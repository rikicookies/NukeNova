<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InterModuleDependencyContractTest extends TestCase
{
    public function testBundledContentModulesUseCoreSearchAndSitemapContracts(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['News', 'Pages', 'Downloads', 'Wiki'] as $module) {
            foreach (glob($root . '/modules/' . $module . '/src/*.php') ?: [] as $file) {
                $source = (string) file_get_contents($file);
                self::assertStringNotContainsString('use Modules\\Search\\src\\Search', $source, $file);
                self::assertStringNotContainsString('use Modules\\Search\\src\\LikePattern', $source, $file);
                self::assertStringNotContainsString('use Modules\\Seo\\src\\SitemapCollecting', $source, $file);
            }
        }
    }

    public function testSharedExtensionContractsLiveInCore(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'app/Core/Search/SearchProviderInterface.php',
            'app/Core/Search/SearchProviderRegistryInterface.php',
            'app/Core/Search/SearchProvidersRegistering.php',
            'app/Core/Search/SearchQuery.php',
            'app/Core/Search/SearchProviderResult.php',
            'app/Core/Search/SearchResultItem.php',
            'app/Core/Search/LikePattern.php',
            'app/Core/Sitemap/SitemapCollecting.php',
        ] as $path) {
            self::assertFileExists($root . '/' . $path, $path);
        }
    }
    public function testBundledModulesDoNotImportOtherModuleInternalsOutsideDemoContent(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (glob($root . '/modules/*/src/*.php') ?: [] as $file) {
            if (str_contains(str_replace('\\', '/', $file), '/modules/DemoContent/')) continue;
            $source = (string) file_get_contents($file);
            self::assertDoesNotMatchRegularExpression('/^use Modules\\(?!' . preg_quote(basename(dirname(dirname($file))), '/') . '\\)[^;]+\\src\\/m', $source, $file);
        }
    }

    public function testOptionalCommentMediaAndMessagingIntegrationsUseCoreContracts(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'app/Core/Comments/CommentProviderInterface.php',
            'app/Core/Comments/CommentTargetChecking.php',
            'app/Core/Comments/CommentCreated.php',
            'app/Core/Media/MediaLibraryInterface.php',
            'app/Core/Media/MediaUsageChecking.php',
            'app/Core/Messaging/PrivateMessageComposerInterface.php',
            'app/Core/Messaging/PrivateMessageSent.php',
            'app/Core/Social/FriendRequested.php',
            'app/Core/Social/FriendAccepted.php',
        ] as $path) self::assertFileExists($root . '/' . $path, $path);
    }

}
