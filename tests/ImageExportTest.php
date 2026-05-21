<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ImageFormat;
use PhpMlKit\Opal\SaveOptions;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageExportTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        $this->outputPath = __DIR__.'/fixtures/output';
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputPath)) {
            $files = glob($this->outputPath.'/*');
            if ($files) {
                array_map('unlink', $files);
            }
            rmdir($this->outputPath);
        }
    }

    // -------------------------------------------------------------------------
    // toBuffer
    // -------------------------------------------------------------------------

    public function testToBufferJpeg(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $buffer = $image->toBuffer(ImageFormat::JPEG);
        $this->assertNotEmpty($buffer);
        $this->assertStringStartsWith("\xFF\xD8\xFF", $buffer); // JPEG magic bytes
    }

    public function testToBufferPng(): void
    {
        $image = Image::blank(10, 10, Color::green());
        $buffer = $image->toBuffer(ImageFormat::PNG);
        $this->assertNotEmpty($buffer);
        $this->assertStringStartsWith("\x89PNG", $buffer); // PNG magic bytes
    }

    public function testToBufferWebP(): void
    {
        $image = Image::blank(10, 10, Color::blue());
        $buffer = $image->toBuffer(ImageFormat::WebP);
        $this->assertNotEmpty($buffer);
    }

    public function testToBufferTiff(): void
    {
        $image = Image::blank(10, 10);
        $buffer = $image->toBuffer(ImageFormat::TIFF);
        $this->assertNotEmpty($buffer);
    }

    public function testToBufferWithOptions(): void
    {
        $image = Image::blank(10, 10);
        $options = SaveOptions::jpeg(90, false);
        $buffer = $image->toBuffer(ImageFormat::JPEG, $options);
        $this->assertNotEmpty($buffer);
    }

    // -------------------------------------------------------------------------
    // toFile
    // -------------------------------------------------------------------------

    public function testToFilePng(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $path = $this->outputPath.'/test-export.png';
        $image->toFile($path);
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testToFileJpeg(): void
    {
        $image = Image::blank(10, 10);
        $path = $this->outputPath.'/test-export.jpg';
        $image->toFile($path);
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testToFileWithOptions(): void
    {
        $image = Image::blank(10, 10);
        $path = $this->outputPath.'/test-export-options.png';
        $options = SaveOptions::png(9);
        $image->toFile($path, $options);
        $this->assertFileExists($path);
    }

    // -------------------------------------------------------------------------
    // toMemory
    // -------------------------------------------------------------------------

    public function testToMemory(): void
    {
        $image = Image::blank(5, 5, Color::red());
        $buffer = $image->toMemory();
        $this->assertNotNull($buffer);
        // 5 * 5 * 3 bytes = 75 bytes for RGB UCHAR
        $this->assertSame(75, \FFI::sizeof($buffer));
    }

    public function testToMemorySizeForGrayscale(): void
    {
        $image = Image::blank(5, 5, colorSpace: ColorSpace::Grayscale);
        $buffer = $image->toMemory();
        $this->assertSame(25, \FFI::sizeof($buffer)); // 5 * 5 * 1
    }

    // -------------------------------------------------------------------------
    // Round-trip: fromFile → toFile → fromFile
    // -------------------------------------------------------------------------

    public function testFileRoundTrip(): void
    {
        $original = Image::blank(20, 20, Color::blue());
        $path = $this->outputPath.'/roundtrip.png';
        $original->toFile($path);
        $reloaded = Image::fromFile($path);
        $this->assertSame($original->width(), $reloaded->width());
        $this->assertSame($original->height(), $reloaded->height());
        $this->assertSame($original->bands(), $reloaded->bands());
    }
}
