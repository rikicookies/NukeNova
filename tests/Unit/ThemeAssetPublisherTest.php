<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Themes\ThemeAssetPublisher;
use NovaNuke\Core\Themes\ThemeManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ThemeAssetPublisherTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/novanuke-theme-' . bin2hex(random_bytes(5));
        mkdir($this->root . '/source/assets/css', 0775, true);
        mkdir($this->root . '/public', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testItPublishesAllowedAssets(): void
    {
        file_put_contents($this->root . '/source/assets/css/theme.css', 'body{}');
        (new ThemeAssetPublisher($this->root . '/public'))->publish($this->manifest());

        self::assertFileExists($this->root . '/public/sample/css/theme.css');
        self::assertSame('body{}', file_get_contents($this->root . '/public/sample/css/theme.css'));
        $this->assertNoPublicationTemporaries();
    }

    public function testItRejectsPhpAssetsBeforeTouchingActiveAssets(): void
    {
        $this->seedActiveAssets();
        file_put_contents($this->root . '/source/assets/css/theme.css', 'new{}');
        file_put_contents($this->root . '/source/assets/unsafe.php', '<?php echo 1;');

        try {
            (new ThemeAssetPublisher($this->root . '/public'))->publish($this->manifest());
            self::fail('Unsupported assets must reject publication.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('extension is not allowed', $error->getMessage());
        }

        self::assertSame('old{}', file_get_contents($this->root . '/public/sample/css/theme.css'));
        $this->assertNoPublicationTemporaries();
    }

    public function testCopyFailureLeavesPreviousAssetsUntouchedAndCleansStaging(): void
    {
        $this->seedActiveAssets();
        file_put_contents($this->root . '/source/assets/css/theme.css', 'new{}');
        file_put_contents($this->root . '/source/assets/app.js', 'new-js');
        $copies = 0;
        $copy = static function (string $source, string $target) use (&$copies): bool {
            $copies++;
            return $copies === 2 ? false : copy($source, $target);
        };

        try {
            (new ThemeAssetPublisher($this->root . '/public', $copy))->publish($this->manifest());
            self::fail('Injected copy failure must abort publication.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('staging', $error->getMessage());
        }

        self::assertSame('old{}', file_get_contents($this->root . '/public/sample/css/theme.css'));
        self::assertFileDoesNotExist($this->root . '/public/sample/app.js');
        $this->assertNoPublicationTemporaries();
    }

    public function testSwapFailureRollsBackPreviousAssets(): void
    {
        $this->seedActiveAssets();
        file_put_contents($this->root . '/source/assets/css/theme.css', 'new{}');
        $renames = 0;
        $rename = static function (string $from, string $to) use (&$renames): bool {
            $renames++;
            return $renames === 2 ? false : rename($from, $to);
        };

        try {
            (new ThemeAssetPublisher($this->root . '/public', null, $rename))->publish($this->manifest());
            self::fail('Injected swap failure must abort publication.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('atomically publish', $error->getMessage());
        }

        self::assertSame('old{}', file_get_contents($this->root . '/public/sample/css/theme.css'));
        $this->assertNoPublicationTemporaries();
    }

    public function testSuccessfulReplacementPublishesCompleteNewTreeAndCleansBackup(): void
    {
        $this->seedActiveAssets();
        file_put_contents($this->root . '/public/sample/obsolete.js', 'obsolete');
        file_put_contents($this->root . '/source/assets/css/theme.css', 'new{}');
        file_put_contents($this->root . '/source/assets/app.js', 'new-js');

        (new ThemeAssetPublisher($this->root . '/public'))->publish($this->manifest());

        self::assertSame('new{}', file_get_contents($this->root . '/public/sample/css/theme.css'));
        self::assertSame('new-js', file_get_contents($this->root . '/public/sample/app.js'));
        self::assertFileDoesNotExist($this->root . '/public/sample/obsolete.js');
        $this->assertNoPublicationTemporaries();
    }

    private function manifest(): ThemeManifest
    {
        return ThemeManifest::fromArray([
            'name' => 'Sample', 'slug' => 'sample', 'version' => '1.0.0', 'cms_min_version' => '0.1.0',
            'layouts' => ['default'], 'positions' => [], 'settings' => [],
        ], $this->root . '/source');
    }

    private function seedActiveAssets(): void
    {
        mkdir($this->root . '/public/sample/css', 0775, true);
        file_put_contents($this->root . '/public/sample/css/theme.css', 'old{}');
    }

    private function assertNoPublicationTemporaries(): void
    {
        $entries = array_values(array_filter(
            scandir($this->root . '/public') ?: [],
            static fn (string $entry): bool => str_contains($entry, '.staging-') || str_contains($entry, '.backup-'),
        ));
        self::assertSame([], $entries);
    }

    private function remove(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $target = $path . '/' . $item;
            is_dir($target) && ! is_link($target) ? $this->remove($target) : unlink($target);
        }
        rmdir($path);
    }
}
