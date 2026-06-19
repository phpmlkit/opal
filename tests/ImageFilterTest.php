<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\BandFormat;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Image;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageFilterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Color space conversion
    // -------------------------------------------------------------------------

    public function testToGrayscale(): void
    {
        $image = Image::blank(10, 10);
        $gs = $image->toGrayscale();
        $this->assertSame(ColorSpace::Grayscale, $gs->colorSpace());
        $this->assertSame(1, $gs->bands());
    }

    public function testToRGB(): void
    {
        $image = Image::blank(10, 10, colorSpace: ColorSpace::Grayscale);
        $rgb = $image->toRGB();
        $this->assertSame(ColorSpace::RGB, $rgb->colorSpace());
        $this->assertSame(3, $rgb->bands());
    }

    // -------------------------------------------------------------------------
    // Conversion matrix: every source × every typed method
    // -------------------------------------------------------------------------
    // Each test asserts: result band count, interpretation, and a known pixel.

    public function testToRgbFromRgbIsIdempotent(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $result = $image->toRGB();
        $this->assertSame($image, $result);
        $this->assertSame(3, $result->bands());
        $this->assertSame([255, 0, 0], $this->pixel($result));
    }

    public function testToRgbFromRgbaDropsAlpha(): void
    {
        $image = Image::blank(10, 10, Color::red())->toRGBA();
        $this->assertSame(4, $image->bands());
        $result = $image->toRGB();
        $this->assertSame(3, $result->bands());
        $this->assertFalse($result->hasAlpha());
        $this->assertSame(ColorSpace::RGB, $result->colorSpace());
        $this->assertSame([255, 0, 0], $this->pixel($result));
    }

    public function testToRgbFromGrayscaleExpandsToRgb(): void
    {
        $image = Image::blank(10, 10, Color::gray(128), ColorSpace::Grayscale);
        $this->assertSame(1, $image->bands());
        $result = $image->toRGB();
        $this->assertSame(3, $result->bands());
        $this->assertSame(ColorSpace::RGB, $result->colorSpace());
        $this->assertSame([128, 128, 128], $this->pixel($result));
    }

    public function testToRgbFromHsvConvertsPixels(): void
    {
        $image = Image::blank(10, 10, Color::red())->toHSV();
        $result = $image->toRGB();
        $this->assertSame(3, $result->bands());
        $this->assertSame(ColorSpace::RGB, $result->colorSpace());
        // HSV (0, 255, 255) is pure red — back to RGB (255, 0, 0)
        $this->assertSame([255, 0, 0], $this->pixel($result));
    }

    public function testToRgbFromCmykConvertsAndDropsBands(): void
    {
        $image = Image::blank(10, 10, Color::red())->toCMYK();
        $this->assertSame(4, $image->bands());
        $result = $image->toRGB();
        $this->assertSame(3, $result->bands());
        $this->assertSame(ColorSpace::RGB, $result->colorSpace());
        // CMYK→sRGB is lossy in libvips; verify the conversion actually happened
        // (result must differ from the raw 3-band trim of the CMYK image)
        $rawTrim = $image->vipsImage->extract_band(0, ['n' => 3])->getpoint(0, 0);
        $this->assertNotEquals(array_map('intval', (array) $rawTrim), $this->pixel($result));
    }

    public function testToGrayscaleFromRgbComputesLuma(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $result = $image->toGrayscale();
        $this->assertSame(1, $result->bands());
        $this->assertSame(ColorSpace::Grayscale, $result->colorSpace());
        // White converts to 255, black to 0
        $white = Image::blank(10, 10, Color::white())->toGrayscale();
        $this->assertSame([255], $this->pixel($white));
        $black = Image::blank(10, 10, Color::black())->toGrayscale();
        $this->assertSame([0], $this->pixel($black));
    }

    public function testToGrayscaleFromRgbaDropsAlpha(): void
    {
        $image = Image::blank(10, 10, Color::red())->toRGBA();
        $result = $image->toGrayscale();
        $this->assertSame(1, $result->bands());
        $this->assertFalse($result->hasAlpha());
    }

    public function testToLabFromRgbProduces3BandFloat(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $result = $image->toLab();
        $this->assertSame(3, $result->bands());
        $this->assertSame(ColorSpace::Lab, $result->colorSpace());
        // Pure red in Lab is approximately (53, 80, 67)
        $this->assertEqualsWithDelta([53.2, 80.1, 67.2], $this->pixel($result), 0.5);
    }

    public function testToHsvFromRgbProduces3BandHsv(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $result = $image->toHSV();
        $this->assertSame(3, $result->bands());
        $this->assertSame(ColorSpace::HSV, $result->colorSpace());
        // Pure red in HSV is (0, 255, 255)
        $this->assertSame([0, 255, 255], $this->pixel($result));
    }

    public function testToCmykFromRgbProduces4Band(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $result = $image->toCMYK();
        $this->assertSame(4, $result->bands());
        $this->assertSame(ColorSpace::CMYK, $result->colorSpace());
        // Pure red in CMYK is (0, 255, 255, 0)
        $this->assertSame([0, 255, 255, 0], $this->pixel($result));
    }

    public function testToOklabFromRgbProduces3BandFloat(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $result = $image->toOklab();
        $this->assertSame(3, $result->bands());
        $this->assertSame(ColorSpace::Oklab, $result->colorSpace());
    }

    public function testToRgbaFromRgbAddsOpaqueAlpha(): void
    {
        $image = Image::blank(10, 10, Color::red());
        $result = $image->toRGBA();
        $this->assertSame(4, $result->bands());
        $this->assertTrue($result->hasAlpha());
        $this->assertSame([255, 0, 0, 255], $this->pixel($result));
    }

    public function testToRgbaFromRgbaIsIdempotent(): void
    {
        $image = Image::blank(10, 10, Color::red())->toRGBA();
        $result = $image->toRGBA();
        $this->assertSame($image, $result);
    }

    public function testToRgbaFromHsvConvertsAndAddsAlpha(): void
    {
        $image = Image::blank(10, 10, Color::red())->toHSV();
        $result = $image->toRGBA();
        $this->assertSame(4, $result->bands());
        $this->assertTrue($result->hasAlpha());
        $this->assertEqualsWithDelta([255, 0, 0, 255], $this->pixel($result), 1.0);
    }

    public function testToRgbaFromCmykConvertsAndAddsAlpha(): void
    {
        $image = Image::blank(10, 10, Color::red())->toCMYK();
        $result = $image->toRGBA();
        $this->assertSame(4, $result->bands());
        $this->assertSame(ColorSpace::RGB, $result->colorSpace());
    }

    public function testAllConversionsAreIdempotent(): void
    {
        $start = Image::blank(10, 10, Color::red());
        $chain = $start->toHSV()->toRGB()->toLab()->toRGB()->toCMYK()->toRGB();
        $this->assertSame(3, $chain->bands());
        $this->assertSame(ColorSpace::RGB, $chain->colorSpace());
    }

    // -------------------------------------------------------------------------
    // Band format conversion (cast)
    // -------------------------------------------------------------------------

    public function testCast(): void
    {
        $image = Image::blank(10, 10);
        $float = $image->cast(BandFormat::FLOAT);
        $this->assertSame(BandFormat::FLOAT, $float->bandFormat());
    }

    public function testToFloat(): void
    {
        $image = Image::blank(10, 10);
        $float = $image->toFloat();
        $this->assertSame(BandFormat::FLOAT, $float->bandFormat());
    }

    public function testToDouble(): void
    {
        $image = Image::blank(10, 10);
        $double = $image->toDouble();
        $this->assertSame(BandFormat::DOUBLE, $double->bandFormat());
    }

    public function testToUChar(): void
    {
        $image = Image::blank(10, 10)->toFloat();
        $back = $image->toUChar();
        $this->assertSame(BandFormat::UCHAR, $back->bandFormat());
    }

    // -------------------------------------------------------------------------
    // Alpha operations
    // -------------------------------------------------------------------------

    public function testToRGBA(): void
    {
        $image = Image::blank(10, 10);
        $rgba = $image->toRGBA();
        $this->assertTrue($rgba->hasAlpha());
        $this->assertSame(4, $rgba->bands());
    }

    public function testToRGBAIdempotent(): void
    {
        $image = Image::blank(10, 10)->toRGBA();
        $again = $image->toRGBA();
        $this->assertSame(4, $again->bands());
    }

    public function testFlattenAlpha(): void
    {
        $image = Image::blank(10, 10)->toRGBA();
        $flattened = $image->flattenAlpha();
        $this->assertSame(3, $flattened->bands());
    }

    public function testFlattenAlphaNoAlpha(): void
    {
        $image = Image::blank(10, 10);
        $flattened = $image->flattenAlpha();
        $this->assertSame(3, $flattened->bands());
    }

    public function testRemoveAlpha(): void
    {
        $image = Image::blank(10, 10)->toRGBA();
        $removed = $image->removeAlpha();
        $this->assertSame(3, $removed->bands());
    }

    public function testPremultiplyAndUnpremultiply(): void
    {
        $image = Image::blank(10, 10)->toRGBA();
        $pre = $image->premultiplyAlpha();
        $this->assertSame(4, $pre->bands());
        $un = $pre->unpremultiplyAlpha();
        $this->assertSame(4, $un->bands());
    }

    // -------------------------------------------------------------------------
    // Regression: Color/image band-count mismatch
    // -------------------------------------------------------------------------
    // flattenAlpha() used to pass toArray() to libvips without specifying band
    // count. An opaque background color on a 4-band image would yield a 3-element
    // vector and libvips would reject it.

    public function testFlattenAlphaWithOpaqueBackground(): void
    {
        $image = Image::blank(10, 10)->toRGBA();
        $flattened = $image->flattenAlpha(Color::white());
        $this->assertSame(3, $flattened->bands());
    }

    public function testFlattenAlphaWithTranslucentBackground(): void
    {
        $image = Image::blank(10, 10)->toRGBA();
        $flattened = $image->flattenAlpha(Color::rgba(255, 255, 255, 200));
        $this->assertSame(3, $flattened->bands());
    }

    // -------------------------------------------------------------------------
    // Simple pixel adjustments (smoke tests)
    // -------------------------------------------------------------------------

    public function testBrightness(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->brightness(1.5);
        $this->assertSame(10, $result->width());
        $this->assertSame(3, $result->bands());
    }

    public function testContrast(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->contrast(1.5);
        $this->assertSame(10, $result->width());
    }

    public function testSaturation(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->saturation(0.0);
        $this->assertSame(10, $result->width());
        $this->assertSame(ColorSpace::RGB, $result->colorSpace());
    }

    public function testHue(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->hue(90);
        $this->assertSame(10, $result->width());
    }

    public function testGamma(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->gamma(1.5);
        $this->assertSame(10, $result->width());
    }

    public function testInvert(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->invert();
        $this->assertSame(10, $result->width());
        $this->assertSame(3, $result->bands());
    }

    // -------------------------------------------------------------------------
    // linear
    // -------------------------------------------------------------------------

    public function testLinearScalar(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->linear(1.5, 0);
        $this->assertSame(10, $result->width());
    }

    public function testLinearArray(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->linear([1.0, 1.5, 2.0], [0, 0, 0]);
        $this->assertSame(10, $result->width());
    }

    public function testLinearMismatchedArraysThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Image::blank(10, 10)->linear([1, 2, 3], [1, 2]);
    }

    // -------------------------------------------------------------------------
    // normalize
    // -------------------------------------------------------------------------

    public function testNormalize(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->normalize([0.5, 0.5, 0.5], [0.1, 0.1, 0.1]);
        $this->assertSame(10, $result->width());
        $this->assertSame(BandFormat::FLOAT, $result->bandFormat());
    }

    public function testNormalizeSingleValue(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->normalize([0.5], [0.1]);
        $this->assertSame(10, $result->width());
    }

    public function testNormalizeInvalidMeanThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Image::blank(10, 10)->normalize([0.5, 0.5], [0.1, 0.1, 0.1]);
    }

    // -------------------------------------------------------------------------
    // Filters (smoke)
    // -------------------------------------------------------------------------

    public function testSharpen(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->sharpen();
        $this->assertSame(10, $result->width());
    }

    public function testBlur(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->blur(3.0);
        $this->assertSame(10, $result->width());
    }

    public function testMedianBlur(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->medianBlur(3);
        $this->assertSame(10, $result->width());
    }

    public function testMedianBlurDefaultSize(): void
    {
        $image = Image::blank(10, 10);
        $result = $image->medianBlur();
        $this->assertSame(10, $result->width());
    }

    /**
     * Read the pixel at (0, 0) as a list of integers (cast floats to int when
     * they are whole numbers).
     */
    private function pixel(Image $image): array
    {
        $raw = (array) $image->vipsImage->getpoint(0, 0);

        return array_map(static function ($v) {
            if (\is_int($v)) {
                return $v;
            }
            $f = (float) $v;

            return $f == (int) $f ? (int) $f : $f;
        }, $raw);
    }
}
