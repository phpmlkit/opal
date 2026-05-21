<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\NDArray\DType;
use PhpMlKit\Opal\BandFormat;
use PhpMlKit\Opal\ChannelFormat;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Exceptions\UnsupportedFormatException;
use PhpMlKit\Opal\ImageFormat;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class EnumTest extends TestCase
{
    // -------------------------------------------------------------------------
    // BandFormat
    // -------------------------------------------------------------------------

    public function testBandFormatStringRoundTrip(): void
    {
        foreach (BandFormat::cases() as $format) {
            $this->assertSame($format, BandFormat::fromString($format->toString()));
        }
    }

    public function testBandFormatDtypeRoundTrip(): void
    {
        foreach (BandFormat::cases() as $format) {
            if (BandFormat::CHAR === $format) {
                continue; // no DType::Int8 in current NDArray
            }
            $this->assertSame($format, BandFormat::fromDtype($format->toDtype()));
        }
    }

    public function testBandFormatUnknownStringFallsBackToFloat(): void
    {
        $this->assertSame(BandFormat::FLOAT, BandFormat::fromString('not-a-format'));
    }

    public function testBandFormatStorageBytes(): void
    {
        $this->assertSame(1, BandFormat::UCHAR->storageBytes());
        $this->assertSame(2, BandFormat::USHORT->storageBytes());
        $this->assertSame(4, BandFormat::FLOAT->storageBytes());
        $this->assertSame(8, BandFormat::DOUBLE->storageBytes());
        $this->assertSame(16, BandFormat::DPCOMPLEX->storageBytes());
    }

    // -------------------------------------------------------------------------
    // ColorSpace
    // -------------------------------------------------------------------------

    public function testColorSpaceBands(): void
    {
        $this->assertSame(1, ColorSpace::Grayscale->bands());
        $this->assertSame(3, ColorSpace::RGB->bands());
        $this->assertSame(3, ColorSpace::BGR->bands());
        $this->assertSame(3, ColorSpace::HSV->bands());
        $this->assertSame(3, ColorSpace::Lab->bands());
        $this->assertSame(4, ColorSpace::RGBA->bands());
        $this->assertSame(4, ColorSpace::BGRA->bands());
        $this->assertSame(4, ColorSpace::CMYK->bands());
    }

    public function testColorSpaceVipsInterpretationRoundTrip(): void
    {
        // Not every color space round-trips exactly (RGB → srgb, etc.)
        // but the mapping should not throw for known ones
        foreach (ColorSpace::cases() as $cs) {
            $vips = $cs->toVipsInterpretation();
            $back = ColorSpace::fromVipsInterpretation($vips);
            $this->assertNotNull($back);
        }
    }

    public function testColorSpaceFromVipsInterpretation(): void
    {
        $this->assertSame(ColorSpace::RGB, ColorSpace::fromVipsInterpretation('srgb'));
        $this->assertSame(ColorSpace::RGB, ColorSpace::fromVipsInterpretation('scrgb'));
        $this->assertSame(ColorSpace::RGB, ColorSpace::fromVipsInterpretation('rgb'));
        $this->assertSame(ColorSpace::Grayscale, ColorSpace::fromVipsInterpretation('b-w'));
        $this->assertSame(ColorSpace::Grayscale, ColorSpace::fromVipsInterpretation('grey'));
    }

    // -------------------------------------------------------------------------
    // ChannelFormat
    // -------------------------------------------------------------------------

    public function testChannelFormatValues(): void
    {
        $this->assertSame('HWC', ChannelFormat::HWC->name);
        $this->assertSame('CHW', ChannelFormat::CHW->name);
    }

    // -------------------------------------------------------------------------
    // ImageFormat
    // -------------------------------------------------------------------------

    public function testImageFormatFromExtension(): void
    {
        $this->assertSame(ImageFormat::JPEG, ImageFormat::fromExtension('jpg'));
        $this->assertSame(ImageFormat::JPEG, ImageFormat::fromExtension('jpeg'));
        $this->assertSame(ImageFormat::PNG, ImageFormat::fromExtension('png'));
        $this->assertSame(ImageFormat::WebP, ImageFormat::fromExtension('webp'));
        $this->assertSame(ImageFormat::TIFF, ImageFormat::fromExtension('tif'));
        $this->assertSame(ImageFormat::TIFF, ImageFormat::fromExtension('tiff'));
        $this->assertSame(ImageFormat::GIF, ImageFormat::fromExtension('gif'));
        $this->assertSame(ImageFormat::AVIF, ImageFormat::fromExtension('avif'));
        $this->assertSame(ImageFormat::HEIF, ImageFormat::fromExtension('heif'));
        $this->assertSame(ImageFormat::HEIF, ImageFormat::fromExtension('heic'));
    }

    public function testImageFormatFromExtensionThrows(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        ImageFormat::fromExtension('psd');
    }

    public function testImageFormatToExtension(): void
    {
        $this->assertSame('jpg', ImageFormat::JPEG->toExtension());
        $this->assertSame('png', ImageFormat::PNG->toExtension());
        $this->assertSame('webp', ImageFormat::WebP->toExtension());
        $this->assertSame('tif', ImageFormat::TIFF->toExtension());
    }

    public function testImageFormatSuffix(): void
    {
        $this->assertSame('.jpg', ImageFormat::JPEG->suffix());
        $this->assertSame('.png', ImageFormat::PNG->suffix());
        $this->assertSame('.webp', ImageFormat::WebP->suffix());
    }
}
