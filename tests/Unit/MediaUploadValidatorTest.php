<?php
declare(strict_types=1);
namespace NovaNuke\Tests\Unit;
use Modules\Media\src\MediaUploadValidator;use PHPUnit\Framework\TestCase;use RuntimeException;
final class MediaUploadValidatorTest extends TestCase
{
 private string $file;
 protected function setUp():void{$this->file=tempnam(sys_get_temp_dir(),'novanuke-media-');file_put_contents($this->file,base64_decode('iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAKklEQVR4nGMwTptJU8QwasGoBaMWjFowasGoBaMWjFowasGoBaMWDBULAKbkyD3xKY9xAAAAAElFTkSuQmCC',true));}
 protected function tearDown():void{if(is_file($this->file))unlink($this->file);}
 public function testItAcceptsVerifiedPngContent():void{$m=(new MediaUploadValidator())->validate(['error'=>UPLOAD_ERR_OK,'tmp_name'=>$this->file,'size'=>filesize($this->file),'name'=>'photo.png']);self::assertSame('image/png',$m->mimeType);self::assertSame(32,$m->width);self::assertSame(32,$m->height);}
 public function testItRejectsAnExtensionMismatch():void{$this->expectException(RuntimeException::class);(new MediaUploadValidator())->validate(['error'=>UPLOAD_ERR_OK,'tmp_name'=>$this->file,'size'=>filesize($this->file),'name'=>'photo.jpg']);}
}
