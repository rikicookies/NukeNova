<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CommentAudienceMutationContractTest extends TestCase
{
    public function testEveryPublicCommentOperationRevalidatesItsParentTarget(): void
    {
        $root = dirname(__DIR__, 2);
        $service = (string) file_get_contents($root . '/modules/Comments/src/CommentService.php');
        $controller = (string) file_get_contents($root . '/modules/Comments/src/PublicCommentsController.php');
        $repository = (string) file_get_contents($root . '/modules/Comments/src/CommentRepository.php');

        self::assertStringContainsString('targetAccess->allows($type, $id)', $service);
        self::assertStringContainsString('targetAccess->require($type, $contentId)', $service);
        self::assertGreaterThanOrEqual(3, substr_count($service, 'assertCommentTargetAccessible('));
        self::assertStringContainsString('targetForComment', $repository);
        self::assertStringContainsString('catch (CommentTargetNotFound)', $controller);
        self::assertStringContainsString("Response::html('Not found.', 404)", $controller);
    }
}
