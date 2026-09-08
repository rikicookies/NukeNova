<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class CommentReactionsTest extends TestCase
{
 public function testReactionsAreAuthenticatedCsrfProtectedAndUnique():void{$root=dirname(__DIR__,2).'/modules/Comments/';$service=file_get_contents($root.'src/CommentService.php');$controller=file_get_contents($root.'src/PublicCommentsController.php');$module=file_get_contents($root.'src/CommentsModule.php');$migration=file_get_contents($root.'database/migrations/2026_09_08_000003_create_comment_reactions.php');self::assertStringContainsString('Sign in to react',$service);self::assertStringContainsString("['like', 'dislike']",$service);self::assertStringContainsString('csrf->validate',$controller);self::assertStringContainsString("'/comments/{id}/react'",$module);self::assertStringContainsString('PRIMARY KEY(comment_id,user_id)',$migration);}
 public function testReactionControlsUsePostAndShowCounts():void{$view=file_get_contents(dirname(__DIR__,2).'/modules/Comments/views/thread.twig');self::assertStringContainsString('method="post" action="/comments/{{ comment.id }}/react"',$view);self::assertStringContainsString('comment.like_count',$view);self::assertStringContainsString('comment.dislike_count',$view);self::assertStringContainsString('comment.viewer_reaction',$view);}
}
