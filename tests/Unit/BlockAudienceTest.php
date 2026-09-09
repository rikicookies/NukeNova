<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class BlockAudienceTest extends TestCase
{
    public function testBlocksUseTheSharedAccessAudienceBeforeRendering():void
    {
        $root=dirname(__DIR__,2);$manager=file_get_contents($root.'/app/Core/Blocks/BlockManager.php');$repository=file_get_contents($root.'/app/Core/Blocks/BlockRepository.php');$view=file_get_contents($root.'/resources/views/admin/blocks/index.twig');
        self::assertStringContainsString('AccessAudience::VALUES',$manager);self::assertStringContainsString('audience->allows',$manager);self::assertStringContainsString('audience=:audience',$repository);self::assertStringContainsString('name="audience"',$view);self::assertStringContainsString('Active VIP only',$view);
    }
}
