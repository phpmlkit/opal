<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ImageFormat;
use PhpMlKit\Opal\TextOptions;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageDrawTest extends TestCase
{
    public function testDrawRect(): void
    {
        $image = Image::blank(50, 50);
        $result = $image->drawRect(10, 10, 30, 20, Color::red());
        $this->assertSame(50, $result->width());
        $this->assertSame(50, $result->height());
    }

    public function testDrawRectFill(): void
    {
        $image = Image::blank(50, 50);
        $result = $image->drawRect(10, 10, 30, 20, Color::blue(), true);
        $this->assertSame(50, $result->width());
    }

    public function testDrawCircle(): void
    {
        $image = Image::blank(50, 50);
        $result = $image->drawCircle(25, 25, 10, Color::green());
        $this->assertSame(50, $result->width());
    }

    public function testDrawCircleFill(): void
    {
        $image = Image::blank(50, 50);
        $result = $image->drawCircle(25, 25, 10, Color::green(), true);
        $this->assertSame(50, $result->width());
    }

    public function testDrawLine(): void
    {
        $image = Image::blank(50, 50);
        $result = $image->drawLine(0, 0, 50, 50, Color::white());
        $this->assertSame(50, $result->width());
    }

    public function testDrawMask(): void
    {
        $base = Image::blank(50, 50, Color::white());
        $mask = Image::blank(30, 30, colorSpace: ColorSpace::Grayscale)->brightness(0.0)->toUChar();
        $result = $base->drawMask($mask, 10, 10, Color::red());
        $this->assertSame(50, $result->width());
        $this->assertSame(50, $result->height());
    }

    public function testDrawText(): void
    {
        $image = Image::blank(200, 100);
        $result = $image->drawText('Hello', 10, 10, Color::white());
        $this->assertSame(200, $result->width());
        $this->assertSame(100, $result->height());
    }

    public function testDrawTextChained(): void
    {
        $image = Image::blank(200, 100, Color::blue());
        $result = $image
            ->drawText('Line 1', 10, 10, Color::white())
            ->drawText('Line 2', 10, 32, Color::white());
        $this->assertSame(200, $result->width());
        $this->assertSame(100, $result->height());
        $this->assertNotEmpty($result->toBuffer(ImageFormat::JPEG));
    }

    public function testDrawTextWithOptions(): void
    {
        $image = Image::blank(300, 100);
        $options = TextOptions::default()->withFontSize(24)->withWidth(280);
        $result = $image->drawText('Wrapped text', 10, 10, Color::white(), $options);
        $this->assertSame(300, $result->width());
    }

    public function testComposite(): void
    {
        $base = Image::blank(100, 100, Color::white());
        $overlay = Image::blank(50, 50, Color::red());
        $result = $base->composite($overlay, 25, 25);
        $this->assertSame(100, $result->width());
        $this->assertSame(100, $result->height());
        // composite2 always produces RGBA output
        $this->assertSame(4, $result->bands());
        $this->assertTrue($result->hasAlpha());
    }

    public function testCompositeAtOrigin(): void
    {
        $base = Image::blank(50, 50, Color::blue());
        $overlay = Image::blank(25, 25, Color::green());
        $result = $base->composite($overlay);
        $this->assertSame(50, $result->width());
        $this->assertSame(50, $result->height());
    }

    // -------------------------------------------------------------------------
    // Regression: Color/image band-count mismatch
    // -------------------------------------------------------------------------
    // These cover the cases where toArray() used to infer its length from
    // the color's alpha value, breaking libvips operations that need the
    // vector length to match the destination image's band count.

    public function testDrawRectOnRgbaImageWithOpaqueColor(): void
    {
        $image = Image::blank(20, 20, Color::red(), ColorSpace::RGB)->toRGBA();
        $this->assertSame(4, $image->bands());
        $result = $image->drawRect(2, 2, 5, 5, Color::blue(), fill: true);
        $this->assertSame(4, $result->bands());
    }

    public function testDrawRectOnRgbImageWithTranslucentColor(): void
    {
        $image = Image::blank(20, 20, Color::red(), ColorSpace::RGB);
        $this->assertSame(3, $image->bands());
        $result = $image->drawRect(2, 2, 5, 5, Color::rgba(0, 255, 0, 128), fill: true);
        $this->assertSame(3, $result->bands());
    }

    public function testDrawRectOnRgbImageWithTransparentColor(): void
    {
        $image = Image::blank(20, 20, Color::red(), ColorSpace::RGB);
        $result = $image->drawRect(2, 2, 5, 5, Color::transparent(), fill: true);
        $this->assertSame(3, $result->bands());
    }

    public function testDrawRectOnGrayscaleImage(): void
    {
        $image = Image::blank(20, 20, Color::gray(64), ColorSpace::Grayscale);
        $this->assertSame(1, $image->bands());
        $result = $image->drawRect(2, 2, 5, 5, Color::white(), fill: true);
        $this->assertSame(1, $result->bands());
    }

    public function testDrawCircleOnRgbaImageWithOpaqueColor(): void
    {
        $image = Image::blank(20, 20, Color::red(), ColorSpace::RGB)->toRGBA();
        $result = $image->drawCircle(10, 10, 5, Color::blue(), fill: true);
        $this->assertSame(4, $result->bands());
    }

    public function testDrawLineOnRgbaImageWithOpaqueColor(): void
    {
        $image = Image::blank(20, 20, Color::red(), ColorSpace::RGB)->toRGBA();
        $result = $image->drawLine(0, 0, 10, 10, Color::green());
        $this->assertSame(4, $result->bands());
    }

    public function testDrawMaskOnRgbaImageWithOpaqueColor(): void
    {
        $mask = Image::blank(10, 10, Color::white(), ColorSpace::Grayscale);
        $image = Image::blank(20, 20, Color::red(), ColorSpace::RGB)->toRGBA();
        $result = $image->drawMask($mask, 2, 2, Color::blue());
        $this->assertSame(4, $result->bands());
    }

    public function testDrawTextOnRgbaImageWithOpaqueColor(): void
    {
        $image = Image::blank(100, 50, Color::red(), ColorSpace::RGB)->toRGBA();
        $result = $image->drawText('Hi', 5, 5, Color::white());
        $this->assertSame(4, $result->bands());
    }

    // -------------------------------------------------------------------------
    // blend flag: alpha compositing
    // -------------------------------------------------------------------------
    // Without blend=true, libvips writes the color directly to destination
    // pixels (replace, not blend). With blend=true the color is alpha-
    // composited onto the source using libvips's `over` blend mode, and the
    // result is always 4-band.

    public function testDrawRectBlendProduces4BandResult(): void
    {
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawRect(5, 5, 10, 10, Color::rgba(255, 0, 0, 128), fill: true, blend: true);
        $this->assertSame(4, $result->bands());
        $this->assertSame(ColorSpace::RGB, $result->colorSpace());
    }

    public function testDrawRectBlendBlendsTranslucentColor(): void
    {
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawRect(5, 5, 10, 10, Color::rgba(255, 0, 0, 128), fill: true, blend: true);
        // 50% red over white ≈ (255, 127, 127, 255) (alpha=128/255 of red over white)
        $px = $result->vipsImage->getpoint(7, 7);
        $this->assertEqualsWithDelta([255.0, 127.0, 127.0, 255.0], array_map('floatval', (array) $px), 1.0);
    }

    public function testDrawRectBlendInvisibleColorLeavesSourceUnchanged(): void
    {
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawRect(5, 5, 10, 10, Color::rgba(255, 0, 0, 0), fill: true, blend: true);
        $px = $result->vipsImage->getpoint(7, 7);
        $this->assertEqualsWithDelta([255.0, 255.0, 255.0, 255.0], array_map('floatval', (array) $px), 1.0);
    }

    public function testDrawRectBlendPreservesPixelsOutsideRect(): void
    {
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawRect(5, 5, 10, 10, Color::rgba(255, 0, 0, 128), fill: true, blend: true);
        // Pixel outside the rect (2, 2) should remain white
        $px = $result->vipsImage->getpoint(2, 2);
        $this->assertEqualsWithDelta([255.0, 255.0, 255.0, 255.0], array_map('floatval', (array) $px), 1.0);
    }

    public function testDrawRectWithoutBlendReplacesNotBlends(): void
    {
        // Default behavior: color replaces destination, alpha is dropped on 3-band
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawRect(5, 5, 10, 10, Color::rgba(255, 0, 0, 128), fill: true);
        $this->assertSame(3, $result->bands());
        $px = $result->vipsImage->getpoint(7, 7);
        $this->assertSame([255, 0, 0], array_map('intval', (array) $px));
    }

    public function testDrawCircleBlendBlendsTranslucentColor(): void
    {
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawCircle(10, 10, 5, Color::rgba(0, 0, 255, 128), fill: true, blend: true);
        $this->assertSame(4, $result->bands());
        $px = $result->vipsImage->getpoint(10, 10);
        $this->assertEqualsWithDelta([127.0, 127.0, 255.0, 255.0], array_map('floatval', (array) $px), 1.0);
    }

    public function testDrawLineBlendBlendsTranslucentColor(): void
    {
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawLine(0, 0, 20, 20, Color::rgba(255, 0, 0, 128), blend: true);
        $this->assertSame(4, $result->bands());
        $px = $result->vipsImage->getpoint(10, 10);
        $this->assertEqualsWithDelta([255.0, 127.0, 127.0, 255.0], array_map('floatval', (array) $px), 1.0);
    }

    public function testDrawMaskBlendBlendsInkThroughStencil(): void
    {
        $mask = Image::blank(20, 20, Color::gray(255), ColorSpace::Grayscale);
        $image = Image::blank(20, 20, Color::white());
        $result = $image->drawMask($mask, 0, 0, Color::rgba(255, 0, 0, 128), blend: true);
        $this->assertSame(4, $result->bands());
        $px = $result->vipsImage->getpoint(10, 10);
        $this->assertEqualsWithDelta([255.0, 127.0, 127.0, 255.0], array_map('floatval', (array) $px), 1.0);
    }

    public function testDrawTextBlendBlendsTranslucentText(): void
    {
        $image = Image::blank(200, 50, Color::white());
        $result = $image->drawText('Hi', 5, 5, Color::rgba(0, 0, 0, 128), blend: true);
        $this->assertSame(4, $result->bands());
    }

    public function testDrawRectBlendOn3BandSourceBecomes4Band(): void
    {
        // Even though source is 3-band, blend produces 4-band output
        $image = Image::blank(20, 20, Color::white());
        $this->assertSame(3, $image->bands());
        $result = $image->drawRect(5, 5, 10, 10, Color::red(), fill: true, blend: true);
        $this->assertSame(4, $result->bands());
    }

    public function testDrawRectBlendIsIdempotentWithOpaqueColor(): void
    {
        // Opaque colors should give the same result whether blend is on or off
        // (on 4-band source, since alpha 255 means full replacement)
        $image = Image::blank(20, 20, Color::white())->toRGBA();
        $noBlend = $image->drawRect(5, 5, 10, 10, Color::red(), fill: true);
        $withBlend = $image->drawRect(5, 5, 10, 10, Color::red(), fill: true, blend: true);
        $this->assertSame(
            array_map('intval', (array) $noBlend->vipsImage->getpoint(7, 7)),
            array_map('intval', (array) $withBlend->vipsImage->getpoint(7, 7))
        );
    }
}
