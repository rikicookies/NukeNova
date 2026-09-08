<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\PrivateMessages\src\PrivateMessageInput;use NovaNuke\Core\Content\ContentFormat;use PHPUnit\Framework\Attributes\DataProvider;use PHPUnit\Framework\TestCase;use RuntimeException;

final class PrivateMessageInputTest extends TestCase
{
    public function testItPreservesMessageSourceForSafeRendering():void{$input=new PrivateMessageInput();self::assertSame('Riki_01',$input->recipient(' Riki_01 '));self::assertSame('Hello world',$input->subject('<b>Hello</b> world'));self::assertSame('<strong>Safe message</strong>',$input->body(' <strong>Safe message</strong> '));self::assertSame(ContentFormat::Markdown,$input->format(null));self::assertSame(ContentFormat::Html,$input->format('html'));}
    #[DataProvider('invalidValues')]
    public function testItRejectsInvalidLengths(string $method,string $value):void{$this->expectException(RuntimeException::class);(new PrivateMessageInput())->{$method}($value);}
    public static function invalidValues():array{return[['recipient','not valid!'],['subject','x'],['body',''],['reason','bad'],['format','unsafe']];}
}
