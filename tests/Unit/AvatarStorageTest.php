<?php

declare(strict_types=1);

namespace NovaNuke\Tests\Unit;

use NovaNuke\Auth\AvatarStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AvatarStorageTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/novanuke-avatars-' . bin2hex(random_bytes(5));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) unlink($file);
        rmdir($this->directory);
    }

    public function testItResolvesOnlyServerGeneratedNamesInsideStorage(): void
    {
        $name = str_repeat('a', 40) . '.png';
        file_put_contents($this->directory . '/' . $name, 'image');
        $avatar = (new AvatarStorage($this->directory))->resolve($name);
        self::assertSame('image/png', $avatar['mime']);
        self::assertSame(5, $avatar['size']);
    }

    public function testItRejectsTraversalAndUnknownNames(): void
    {
        $this->expectException(RuntimeException::class);
        (new AvatarStorage($this->directory))->resolve('../avatar.php');
    }
}
