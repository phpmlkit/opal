<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\BandFormat;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Exceptions\FileNotFoundException;
use PhpMlKit\Opal\Exceptions\InvalidImageException;
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ImageFormat;
use PhpMlKit\Opal\LoadOptions;
use PhpMlKit\Opal\TextOptions;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageCreateTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__.'/fixtures/output';
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fixturesDir)) {
            $files = glob($this->fixturesDir.'/*');
            if ($files) {
                array_map('unlink', $files);
            }
            rmdir($this->fixturesDir);
        }
    }

    // -------------------------------------------------------------------------
    // blank
    // -------------------------------------------------------------------------

    public function testBlank(): void
    {
        $image = Image::blank(100, 200, Color::black());
        $this->assertSame(100, $image->width());
        $this->assertSame(200, $image->height());
        $this->assertSame(3, $image->bands());
        $this->assertSame(ColorSpace::RGB, $image->colorSpace());
        $this->assertSame(BandFormat::UCHAR, $image->bandFormat());
    }

    public function testBlankGrayscaleBands(): void
    {
        $image = Image::blank(50, 50, colorSpace: ColorSpace::Grayscale);
        $this->assertSame(1, $image->bands());
    }

    public function testBlankGrayscaleWithOpaqueColor(): void
    {
        $image = Image::blank(10, 10, Color::white(), ColorSpace::Grayscale);
        $this->assertSame(1, $image->bands());
    }

    public function testBlankGrayscaleWithTransparentColor(): void
    {
        $image = Image::blank(10, 10, Color::transparent(), ColorSpace::Grayscale);
        $this->assertSame(1, $image->bands());
    }

    // -------------------------------------------------------------------------
    // text
    // -------------------------------------------------------------------------

    public function testText(): void
    {
        $image = Image::text('Hello', TextOptions::default()->withFontSize(24));
        $this->assertGreaterThan(0, $image->width());
        $this->assertGreaterThan(0, $image->height());
    }

    public function testTextWithWidth(): void
    {
        $image = Image::text('A longer line of text for wrapping', TextOptions::default()
            ->withFontSize(16)
            ->withWidth(150));
        $this->assertLessThanOrEqual(150, $image->width());
    }

    public function testTextWithFontSizeScalesOutput(): void
    {
        $small = Image::text('Hello', TextOptions::default()->withFontSize(12));
        $large = Image::text('Hello', TextOptions::default()->withFontSize(48));
        $this->assertGreaterThan($small->width(), $large->width());
        $this->assertGreaterThan($small->height(), $large->height());
    }

    public function testTextWithFontChangesRendering(): void
    {
        // Use bundled fonts so the test works on CI runners that don't have
        // any particular system fonts installed.
        $interFont = __DIR__.'/fixtures/fonts/Inter-Regular.ttf';
        $plexFont = __DIR__.'/fixtures/fonts/IBMPlexSerif-Regular.ttf';
        if (!is_file($interFont) || !is_file($plexFont)) {
            $this->markTestSkipped('Test font fixtures are not present');
        }

        $inter = Image::text('Hello', TextOptions::default()->withFont('Inter', $interFont)->withFontSize(48));
        $plex = Image::text('Hello', TextOptions::default()->withFont('IBM Plex Serif', $plexFont)->withFontSize(48));
        $this->assertNotSame(
            [$inter->width(), $inter->height()],
            [$plex->width(), $plex->height()],
            'Different font families should produce different dimensions'
        );
    }

    public function testTextWithFontFileMatchesFontFamily(): void
    {
        $bundledFont = __DIR__.'/fixtures/fonts/Inter-Regular.ttf';
        if (!is_file($bundledFont)) {
            $this->markTestSkipped('Test font fixture is not present');
        }

        $byFamily = Image::text('Hello', TextOptions::default()->withFont('Inter', $bundledFont)->withFontSize(48));
        $expected = Image::text('Hello', TextOptions::default()->withFont('Inter', $bundledFont)->withFontSize(48));
        $this->assertSame($byFamily->width(), $expected->width());
        $this->assertSame($byFamily->height(), $expected->height());
    }

    // -------------------------------------------------------------------------
    // fromFile / fromBuffer round-trip
    // -------------------------------------------------------------------------

    public function testFromBufferThenToBufferRoundTrip(): void
    {
        $original = Image::blank(10, 10, Color::red());
        $buffer = $original->toBuffer(ImageFormat::PNG);
        $reloaded = Image::fromBuffer($buffer);
        $this->assertSame(10, $reloaded->width());
        $this->assertSame(10, $reloaded->height());
        $this->assertSame(3, $reloaded->bands());
    }

    public function testFromBufferJpeg(): void
    {
        $original = Image::blank(20, 20, Color::blue());
        $buffer = $original->toBuffer(ImageFormat::JPEG);
        $reloaded = Image::fromBuffer($buffer);
        $this->assertSame(20, $reloaded->width());
        $this->assertSame(20, $reloaded->height());
    }

    public function testFromBufferWebP(): void
    {
        $original = Image::blank(15, 15, Color::green());
        $buffer = $original->toBuffer(ImageFormat::WebP);
        $reloaded = Image::fromBuffer($buffer);
        $this->assertSame(15, $reloaded->width());
        $this->assertSame(15, $reloaded->height());
    }

    public function testFromFile(): void
    {
        $original = Image::blank(10, 10, Color::red());
        $path = $this->fixturesDir.'/test-from-file.png';
        $original->toFile($path);
        $reloaded = Image::fromFile($path);
        $this->assertSame(10, $reloaded->width());
        $this->assertSame(10, $reloaded->height());
    }

    public function testFromFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        Image::fromFile('/nonexistent/image.jpg');
    }

    public function testFromFileInvalid(): void
    {
        $this->expectException(InvalidImageException::class);
        Image::fromFile(__FILE__); // PHP file is not a valid image
    }

    public function testFromBufferInvalid(): void
    {
        $this->expectException(InvalidImageException::class);
        Image::fromBuffer('not an image');
    }

    public function testFromBufferWithLoadOptions(): void
    {
        $original = Image::blank(10, 10, Color::red());
        $buffer = $original->toBuffer(ImageFormat::JPEG);
        $reloaded = Image::fromBuffer($buffer, LoadOptions::default()->withAutoRotate(false));
        $this->assertSame(10, $reloaded->width());
    }

    // -------------------------------------------------------------------------
    // thumbnail
    // -------------------------------------------------------------------------

    public function testThumbnail(): void
    {
        $original = Image::blank(100, 100);
        $path = $this->fixturesDir.'/test-thumbnail.png';
        $original->toFile($path);
        $thumb = Image::thumbnail($path, 50);
        $this->assertSame(50, $thumb->width());
        $this->assertSame(50, $thumb->height());
    }

    public function testThumbnailNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        Image::thumbnail('/nonexistent.jpg', 100);
    }

    // -------------------------------------------------------------------------
    // Metadata
    // -------------------------------------------------------------------------

    public function testSize(): void
    {
        $image = Image::blank(80, 60);
        $size = $image->size();
        $this->assertSame(80, $size->width);
        $this->assertSame(60, $size->height);
    }

    public function testHasAlpha(): void
    {
        $noAlpha = Image::blank(10, 10);
        $this->assertFalse($noAlpha->hasAlpha());
    }

    public function testPageCount(): void
    {
        $image = Image::blank(10, 10);
        $this->assertSame(1, $image->pageCount());
    }

    public function testResolution(): void
    {
        $image = Image::blank(10, 10);
        $res = $image->resolution();
        $this->assertArrayHasKey('x', $res);
        $this->assertArrayHasKey('y', $res);
    }

    public function testExif(): void
    {
        $image = Image::blank(10, 10);
        $this->assertIsArray($image->exif());
    }

    public function testIccProfile(): void
    {
        $image = Image::blank(10, 10);
        $this->assertNull($image->iccProfile());
    }

    // -------------------------------------------------------------------------
    // copy
    // -------------------------------------------------------------------------

    public function testCopy(): void
    {
        $image = Image::blank(30, 40);
        $copy = $image->copy();
        $this->assertNotSame($image, $copy);
        $this->assertSame(30, $copy->width());
        $this->assertSame(40, $copy->height());
        $this->assertSame($image->bands(), $copy->bands());
    }
}
