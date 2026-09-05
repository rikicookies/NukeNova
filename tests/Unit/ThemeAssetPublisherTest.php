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
    }

    public function testItRejectsPhpAssets(): void
    {
        file_put_contents($this->root . '/source/assets/unsafe.php', '<?php echo 1;');
        $this->expectException(RuntimeException::class);

        (new ThemeAssetPublisher($this->root . '/public'))->publish($this->manifest());
    }

    private function manifest(): ThemeManifest
    {
        return ThemeManifest::fromArray([
            'name' => 'Sample', 'slug' => 'sample', 'version' => '1.0.0', 'cms_min_version' => '0.1.0',
            'layouts' => ['default'], 'positions' => [], 'settings' => [],
        ], $this->root . '/source');
    }

    private function remove(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $target = $path . '/' . $item;
            is_dir($target) ? $this->remove($target) : unlink($target);
        }
        rmdir($path);
    }
}
