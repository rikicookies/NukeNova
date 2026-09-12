<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PrivateFileServingContractTest extends TestCase
{
    public function testDownloadsAuthorizeBeforeResolvingPrivateStorage(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Downloads/src/DownloadManager.php');
        $method = $this->method($source, 'public function prepare(');

        self::assertLessThan(
            strpos($method, '$this->storage->path'),
            strpos($method, '$this->repository->canView'),
        );
    }

    public function testWikiAttachmentsAuthorizeBeforeResolvingPrivateStorage(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/modules/Wiki/src/PublicWikiController.php');
        $method = $this->method($source, 'public function attachment(');

        self::assertLessThan(
            strpos($method, '$this->attachments->path'),
            strpos($method, '$this->pages->canView'),
        );
        self::assertStringContainsString("'Cache-Control' => 'private, no-store'", $method);
    }

    public function testAvatarServingRemainsExplicitlyPublicButNosniffProtected(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Auth/PublicProfileController.php');
        $method = $this->method($source, 'function avatar(');

        self::assertStringContainsString("'Content-Disposition' => 'inline'", $method);
        self::assertStringContainsString("'Cache-Control' => 'public, max-age=86400, immutable'", $method);
        self::assertStringContainsString("'X-Content-Type-Options' => 'nosniff'", $method);
    }

    private function method(string $source, string $needle): string
    {
        $start = strpos($source, $needle);
        self::assertNotFalse($start);
        return substr($source, $start, 6000);
    }
}
