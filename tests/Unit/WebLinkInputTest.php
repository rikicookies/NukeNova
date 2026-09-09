<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\WebLinks\src\WebLinkInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WebLinkInputTest extends TestCase
{
    public function testItAcceptsAFormattedWebLinkAndPreservesSource(): void
    {
        $data = (new WebLinkInput())->link([
            'title' => 'Example', 'slug' => 'example', 'url' => 'https://example.com/path?q=1',
            'description' => '<p>Safe</p><script>bad()</script>', 'description_format' => 'html',
            'status' => 'published', 'is_featured' => '1',
        ], true);
        self::assertSame('https://example.com/path?q=1', $data['url']);
        self::assertStringContainsString('<script', $data['description']);
        self::assertSame('html', $data['description_format']);
        self::assertSame(1, $data['is_featured']);
    }

    #[DataProvider('dangerousUrls')]
    public function testItRejectsDangerousUrls(string $url): void
    {
        $this->expectException(RuntimeException::class);
        (new WebLinkInput())->url($url);
    }

    public function testItRejectsUnknownFormats(): void
    {
        $this->expectException(RuntimeException::class);
        (new WebLinkInput())->link(['title' => 'Example', 'slug' => 'example', 'url' => 'https://example.com', 'description' => 'Text', 'description_format' => 'php'], true);
    }

    public function testOnlyAdministratorsMayChooseRestrictedAudience(): void
    {
        $input = ['title' => 'VIP', 'slug' => 'vip', 'url' => 'https://example.com', 'description' => 'Private', 'audience' => 'vip'];
        self::assertSame('vip', (new WebLinkInput())->link($input, true)['audience']);
        self::assertSame('public', (new WebLinkInput())->link($input, false)['audience']);

        $this->expectException(RuntimeException::class);
        (new WebLinkInput())->link(array_replace($input, ['audience' => 'administrator']), true);
    }

    public static function dangerousUrls(): array
    {
        return [['javascript:alert(1)'], ['file:///etc/passwd'], ['https://user:pass@example.com'], ["https://example.com\nInjected"]];
    }
}
