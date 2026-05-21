<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\BoundingBox;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\CompassDirection;
use PhpMlKit\Opal\Exceptions\ShapeException;
use PhpMlKit\Opal\FlipDirection;
use PhpMlKit\Opal\Image;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageTransformTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Resize
    // -------------------------------------------------------------------------

    public function testResize(): void
    {
        $image = Image::blank(100, 100);
        $resized = $image->resize(50, 50);
        $this->assertSame(50, $resized->width());
        $this->assertSame(50, $resized->height());
    }

    public function testResizeNonSquare(): void
    {
        $image = Image::blank(200, 100);
        $resized = $image->resize(100, 50);
        $this->assertSame(100, $resized->width());
        $this->assertSame(50, $resized->height());
    }

    public function testResizeToWidth(): void
    {
        $image = Image::blank(200, 100);
        $resized = $image->resizeToWidth(50);
        $this->assertSame(50, $resized->width());
        $this->assertSame(25, $resized->height());
    }

    public function testResizeToHeight(): void
    {
        $image = Image::blank(200, 100);
        $resized = $image->resizeToHeight(50);
        $this->assertSame(100, $resized->width());
        $this->assertSame(50, $resized->height());
    }

    public function testScale(): void
    {
        $image = Image::blank(200, 100);
        $scaled = $image->scale(0.5);
        $this->assertSame(100, $scaled->width());
        $this->assertSame(50, $scaled->height());
    }

    public function testScaleUp(): void
    {
        $image = Image::blank(10, 10);
        $scaled = $image->scale(2.0);
        $this->assertSame(20, $scaled->width());
        $this->assertSame(20, $scaled->height());
    }

    public function testResizeNegativeWidthThrows(): void
    {
        $this->expectException(ShapeException::class);
        Image::blank(100, 100)->resize(0, 50);
    }

    // -------------------------------------------------------------------------
    // Crop
    // -------------------------------------------------------------------------

    public function testCrop(): void
    {
        $image = Image::blank(100, 100);
        $cropped = $image->crop(10, 10, 50, 50);
        $this->assertSame(50, $cropped->width());
        $this->assertSame(50, $cropped->height());
    }

    public function testCropInvalidParamsThrows(): void
    {
        $this->expectException(ShapeException::class);
        Image::blank(100, 100)->crop(-1, 0, 50, 50);
    }

    public function testCropOutOfBoundsThrows(): void
    {
        $this->expectException(ShapeException::class);
        Image::blank(100, 100)->crop(80, 80, 50, 50);
    }

    public function testCenterCrop(): void
    {
        $image = Image::blank(200, 100);
        $cropped = $image->centerCrop(50, 50);
        $this->assertSame(50, $cropped->width());
        $this->assertSame(50, $cropped->height());
    }

    public function testCenterCropLargerThanImage(): void
    {
        $image = Image::blank(50, 50);
        $cropped = $image->centerCrop(100, 100);
        $this->assertSame(100, $cropped->width());
        $this->assertSame(100, $cropped->height());
    }

    public function testCenterCropThrowsOnZero(): void
    {
        $this->expectException(ShapeException::class);
        Image::blank(100, 100)->centerCrop(0, 50);
    }

    public function testCropBoundingBox(): void
    {
        $image = Image::blank(100, 100);
        $box = new BoundingBox(10, 10, 50, 50);
        $cropped = $image->cropBoundingBox($box);
        $this->assertSame(50, $cropped->width());
        $this->assertSame(50, $cropped->height());
    }

    // -------------------------------------------------------------------------
    // Pad / Letterbox
    // -------------------------------------------------------------------------

    public function testPad(): void
    {
        $image = Image::blank(100, 100);
        $padded = $image->pad(10, 20, 30, 40);
        $this->assertSame(160, $padded->width());  // 100 + 40 + 20
        $this->assertSame(140, $padded->height()); // 100 + 10 + 30
    }

    public function testPadWithColor(): void
    {
        $image = Image::blank(10, 10);
        $padded = $image->pad(5, 5, 5, 5, Color::red());
        $this->assertSame(20, $padded->width());
        $this->assertSame(20, $padded->height());
    }

    public function testLetterbox(): void
    {
        $image = Image::blank(200, 100);
        $letterboxed = $image->letterbox(100, 100);
        $this->assertSame(100, $letterboxed->width());
        $this->assertSame(100, $letterboxed->height());
    }

    public function testLetterboxTaller(): void
    {
        $image = Image::blank(100, 200);
        $letterboxed = $image->letterbox(100, 100);
        $this->assertSame(100, $letterboxed->width());
        $this->assertSame(100, $letterboxed->height());
    }

    public function testPadToSize(): void
    {
        $image = Image::blank(50, 50);
        $padded = $image->padToSize(100, 100);
        $this->assertSame(100, $padded->width());
        $this->assertSame(100, $padded->height());
    }

    public function testPadToSizeWithDirection(): void
    {
        $image = Image::blank(50, 50);
        $padded = $image->padToSize(100, 100, Color::black(), CompassDirection::NORTH_EAST);
        $this->assertSame(100, $padded->width());
        $this->assertSame(100, $padded->height());
    }

    // -------------------------------------------------------------------------
    // Flip
    // -------------------------------------------------------------------------

    public function testFlipHorizontal(): void
    {
        $image = Image::blank(100, 50);
        $flipped = $image->flip(FlipDirection::Horizontal);
        $this->assertSame(100, $flipped->width());
        $this->assertSame(50, $flipped->height());
    }

    public function testFlipVertical(): void
    {
        $image = Image::blank(100, 50);
        $flipped = $image->flip(FlipDirection::Vertical);
        $this->assertSame(100, $flipped->width());
        $this->assertSame(50, $flipped->height());
    }

    public function testFlipBoth(): void
    {
        $image = Image::blank(100, 50);
        $flipped = $image->flip(FlipDirection::Both);
        $this->assertSame(100, $flipped->width());
        $this->assertSame(50, $flipped->height());
    }

    // -------------------------------------------------------------------------
    // Rotate
    // -------------------------------------------------------------------------

    public function testRotate(): void
    {
        $image = Image::blank(100, 100);
        $rotated = $image->rotate(45);
        $this->assertGreaterThan(100, $rotated->width());
        $this->assertGreaterThan(100, $rotated->height());
    }

    public function testRotateWithBackground(): void
    {
        $image = Image::blank(100, 100);
        $rotated = $image->rotate(45, Color::black());
        $this->assertGreaterThan(100, $rotated->width());
    }

    public function testRot90Square(): void
    {
        $image = Image::blank(100, 100);
        $rotated = $image->rot90();
        $this->assertSame(100, $rotated->width());
        $this->assertSame(100, $rotated->height());
    }

    public function testRot90NonSquareSwapsDimensions(): void
    {
        $image = Image::blank(200, 100);
        $rotated = $image->rot90();
        $this->assertSame(100, $rotated->width());
        $this->assertSame(200, $rotated->height());
    }

    public function testRot180(): void
    {
        $image = Image::blank(200, 100);
        $rotated = $image->rot180();
        $this->assertSame(200, $rotated->width());
        $this->assertSame(100, $rotated->height());
    }

    public function testRot270SwapsDimensions(): void
    {
        $image = Image::blank(200, 100);
        $rotated = $image->rot270();
        $this->assertSame(100, $rotated->width());
        $this->assertSame(200, $rotated->height());
    }

    public function testAutoRotate(): void
    {
        $image = Image::blank(100, 100);
        $rotated = $image->autoRotate();
        $this->assertSame(100, $rotated->width());
        $this->assertSame(100, $rotated->height());
    }
}
