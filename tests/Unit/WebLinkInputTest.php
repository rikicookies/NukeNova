<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\WebLinks\src\WebLinkInput;use NovaNuke\Core\Security\HtmlSanitizer;use PHPUnit\Framework\Attributes\DataProvider;use PHPUnit\Framework\TestCase;use RuntimeException;

final class WebLinkInputTest extends TestCase
{
    public function testItAcceptsAndSanitizesASafeWebLink():void{$data=(new WebLinkInput(new HtmlSanitizer()))->link(['title'=>'Example','slug'=>'example','url'=>'https://example.com/path?q=1','description'=>'<p>Safe</p><script>bad()</script>','status'=>'published','is_featured'=>'1'],true);self::assertSame('https://example.com/path?q=1',$data['url']);self::assertStringNotContainsString('<script',$data['description']);self::assertSame(1,$data['is_featured']);}
    #[DataProvider('dangerousUrls')]
    public function testItRejectsDangerousUrls(string $url):void{$this->expectException(RuntimeException::class);(new WebLinkInput(new HtmlSanitizer()))->url($url);}
    public static function dangerousUrls():array{return[['javascript:alert(1)'],['file:///etc/passwd'],['https://user:pass@example.com'],["https://example.com\nInjected"]];}
}
