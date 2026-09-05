<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\PrivateMessages\src\PrivateMessageInput;use PHPUnit\Framework\Attributes\DataProvider;use PHPUnit\Framework\TestCase;use RuntimeException;

final class PrivateMessageInputTest extends TestCase
{
    public function testItRemovesHtmlAndKeepsPlainText():void{$input=new PrivateMessageInput();self::assertSame('Riki_01',$input->recipient(' Riki_01 '));self::assertSame('Hello world',$input->subject('<b>Hello</b> world'));self::assertSame('Safe message',$input->body('<script></script>Safe message'));}
    #[DataProvider('invalidValues')]
    public function testItRejectsInvalidLengths(string $method,string $value):void{$this->expectException(RuntimeException::class);(new PrivateMessageInput())->{$method}($value);}
    public static function invalidValues():array{return[['recipient','not valid!'],['subject','x'],['body',''],['reason','bad']];}
}
