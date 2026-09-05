<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Comments\src\CommentTargetChecking;
use PHPUnit\Framework\TestCase;

final class CommentTargetCheckingTest extends TestCase
{
    public function testAContentProviderMustExplicitlyAcceptTheTarget(): void
    {
        $target = new CommentTargetChecking('news', 42);
        self::assertFalse($target->accepted);

        $target->accept();
        self::assertTrue($target->accepted);
    }
}
