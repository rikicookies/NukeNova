<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use Modules\Downloads\src\DownloadUploadValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DownloadUploadValidatorTest extends TestCase
{
    public function testItUsesServerInspectedMimeAndSize(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'nova-upload-');
        file_put_contents($path, 'plain text download');
        try {
            $upload = (new DownloadUploadValidator())->validate(['error' => UPLOAD_ERR_OK, 'tmp_name' => $path, 'name' => '../notes.txt', 'size' => filesize($path)]);
            self::assertSame('notes.txt', $upload?->originalName);
            self::assertSame('txt', $upload?->extension);
            self::assertSame('text/plain', $upload?->mimeType);
        } finally { @unlink($path); }
    }

    public function testItRejectsDisallowedExtensionsEvenWhenMimeIsPlainText(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'nova-upload-'); file_put_contents($path, '<?php echo 1;');
        try {
            $this->expectException(RuntimeException::class);
            (new DownloadUploadValidator())->validate(['error' => UPLOAD_ERR_OK, 'tmp_name' => $path, 'name' => 'payload.php', 'size' => filesize($path)]);
        } finally { @unlink($path); }
    }
}
