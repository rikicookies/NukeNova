<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiAttachmentManager;
use Modules\Wiki\src\WikiAttachmentUpload;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WikiAttachmentManagerTest extends TestCase
{
    public function testItRejectsStoredNamesThatCouldTraverseDirectories(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiAttachmentManager(
            $this->createStub(PDO::class),
            new WikiAttachmentUpload(),
            sys_get_temp_dir(),
        ))->path('../private.txt');
    }
}
