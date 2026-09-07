<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Core\Blocks\BlockRegions;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class BlockRegionsTest extends TestCase
{
    public function testRegionsCanBeFilledAfterTwigHasInitialized(): void
    {
        $regions = new BlockRegions(['left-sidebar', 'right-sidebar']);
        $twig = new Environment(new ArrayLoader([
            'region.twig' => '{{ blocks["left-sidebar"]|length }}:{{ blocks["right-sidebar"]|length }}',
        ]), ['strict_variables' => true]);
        $twig->addGlobal('blocks', $regions);

        self::assertSame('0:0', $twig->render('region.twig'));
        $regions->add('left-sidebar', ['html' => 'First']);
        $regions->add('right-sidebar', ['html' => 'Second']);
        self::assertSame('1:1', $twig->render('region.twig'));
    }
}
