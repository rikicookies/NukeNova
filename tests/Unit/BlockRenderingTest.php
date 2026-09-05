<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Blocks\BlockRendering;
use PHPUnit\Framework\TestCase;

final class BlockRenderingTest extends TestCase
{
    public function testAModuleCanProvideRenderedBlockContent():void
    {
        $event=new BlockRendering(['type'=>'polls-active']);
        self::assertNull($event->html);
        $event->render('<form>Poll</form>');
        self::assertSame('<form>Poll</form>',$event->html);
    }
}
