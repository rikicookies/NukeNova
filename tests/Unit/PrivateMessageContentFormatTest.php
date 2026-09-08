<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PrivateMessageContentFormatTest extends TestCase
{
    public function testModulePersistsAndSafelyRendersMessageFormats(): void
    {
        $root=dirname(__DIR__,2).'/modules/PrivateMessages/';
        $repository=file_get_contents($root.'src/PrivateMessageRepository.php');
        $service=file_get_contents($root.'src/PrivateMessageService.php');
        $show=file_get_contents($root.'views/show.twig');
        $compose=file_get_contents($root.'views/compose.twig');
        self::assertStringContainsString('body_format', $repository);
        self::assertStringContainsString('ContentProfile::Message', $service);
        self::assertStringContainsString('new Markup', $service);
        self::assertStringContainsString('item.body_html', $show);
        self::assertStringContainsString('name="body_format"', $compose);
        self::assertStringContainsString('name="body_format"', $show);
    }
}
