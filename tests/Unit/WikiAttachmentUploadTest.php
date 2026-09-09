<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Wiki\src\WikiAttachmentUpload;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WikiAttachmentUploadTest extends TestCase
{
    public function testItUsesTheServerInspectedMimeAndAPathSafeOriginalName(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'wiki-attachment-');
        file_put_contents($path, "# Installation\n");
        try {
            $upload = (new WikiAttachmentUpload())->validate([
                'error' => UPLOAD_ERR_OK,
                'tmp_name' => $path,
                'name' => '../installation.md',
                'size' => filesize($path),
            ]);
            self::assertSame('installation.md', $upload['original_name']);
            self::assertSame('md', $upload['extension']);
            self::assertSame('text/plain', $upload['mime_type']);
        } finally {
            @unlink($path);
        }
    }

    public function testItRejectsExecutableExtensions(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'wiki-attachment-');
        file_put_contents($path, '<?php echo 1;');
        try {
            $this->expectException(RuntimeException::class);
            (new WikiAttachmentUpload())->validate([
                'error' => UPLOAD_ERR_OK,
                'tmp_name' => $path,
                'name' => 'payload.php',
                'size' => filesize($path),
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function testItRejectsMissingUploads(): void
    {
        $this->expectException(RuntimeException::class);
        (new WikiAttachmentUpload())->validate(null);
    }
}
