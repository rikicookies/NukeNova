<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Polls\src\PollInput;use PHPUnit\Framework\Attributes\DataProvider;use PHPUnit\Framework\TestCase;use RuntimeException;

final class PollInputTest extends TestCase
{
    public function testItValidatesAndNormalizesAMultipleChoicePoll():void{$data=(new PollInput())->poll(['question'=>'<b>Best color?</b>','options'=>"Blue\nGreen\nRed",'status'=>'active','allow_multiple'=>'1','max_selections'=>'9','starts_at'=>'2026-09-01T10:00','ends_at'=>'2026-09-02T10:00']);self::assertSame('Best color?',$data['question']);self::assertSame(['Blue','Green','Red'],$data['options']);self::assertSame(3,$data['max_selections']);self::assertSame('2026-09-01 10:00:00',$data['starts_at']);}
    #[DataProvider('invalidPolls')]
    public function testItRejectsInvalidPollDefinitions(array $input):void{$this->expectException(RuntimeException::class);(new PollInput())->poll($input);}
    public static function invalidPolls():array{return[[['question'=>'No?','options'=>"Yes\nNo"]],[['question'=>'Valid question?','options'=>'Only one']],[['question'=>'Valid question?','options'=>"Yes\nyes"]],[['question'=>'Valid question?','options'=>"Yes\nNo",'starts_at'=>'2026-09-02T10:00','ends_at'=>'2026-09-01T10:00']]];}
}
