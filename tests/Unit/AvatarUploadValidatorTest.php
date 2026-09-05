<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\AvatarUploadValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AvatarUploadValidatorTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'novanuke-avatar-');
        file_put_contents($this->file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAKklEQVR4nGMwTptJU8QwasGoBaMWjFowasGoBaMWjFowasGoBaMWDBULAKbkyD3xKY9xAAAAAElFTkSuQmCC', true));
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) unlink($this->file);
    }

    public function testItAcceptsVerifiedImageContent(): void
    {
        $avatar = (new AvatarUploadValidator())->validate([
            'error' => UPLOAD_ERR_OK, 'tmp_name' => $this->file, 'size' => filesize($this->file), 'name' => 'ignored.php.png',
        ]);
        self::assertSame('png', $avatar->extension);
        self::assertSame('image/png', $avatar->mimeType);
    }

    public function testItRejectsMismatchedOrOversizedContent(): void
    {
        file_put_contents($this->file, '<?php echo 1;');
        $this->expectException(RuntimeException::class);
        (new AvatarUploadValidator())->validate([
            'error' => UPLOAD_ERR_OK, 'tmp_name' => $this->file, 'size' => filesize($this->file), 'name' => 'avatar.png',
        ]);
    }
}
