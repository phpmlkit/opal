<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\BoundingBox;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ImageException;
use PhpMlKit\Opal\ImageSize;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ValueTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Color
    // -------------------------------------------------------------------------

    public function testColorRgb(): void
    {
        $color = Color::rgb(255, 0, 0);
        $this->assertSame(255, $color->r());
        $this->assertSame(0, $color->g());
        $this->assertSame(0, $color->b());
        $this->assertSame(255, $color->a());
    }

    public function testColorRgba(): void
    {
        $color = Color::rgba(100, 200, 50, 128);
        $this->assertSame(100, $color->r());
        $this->assertSame(200, $color->g());
        $this->assertSame(50, $color->b());
        $this->assertSame(128, $color->a());
    }

    public function testColorGray(): void
    {
        $color = Color::gray(128);
        $this->assertSame(128, $color->r());
        $this->assertSame(128, $color->g());
        $this->assertSame(128, $color->b());
        $this->assertSame(255, $color->a());
    }

    public function testColorFromHex(): void
    {
        $this->assertEquals(Color::rgb(255, 0, 0), Color::fromHex('#ff0000'));
        $this->assertEquals(Color::rgb(255, 0, 0), Color::fromHex('#f00'));
        $this->assertEquals(Color::rgba(255, 0, 0, 128), Color::fromHex('#ff000080'));
    }

    public function testColorFromHexInvalidThrows(): void
    {
        $this->expectException(ImageException::class);
        Color::fromHex('#xyz');
    }

    public function testColorNamedConstructors(): void
    {
        $this->assertSame(0, Color::black()->r());
        $this->assertSame(255, Color::white()->r());
        $this->assertSame(255, Color::red()->r());
        $this->assertSame(255, Color::green()->g());
        $this->assertSame(255, Color::blue()->b());
        $this->assertSame(0, Color::transparent()->a());
    }

    public function testColorToArray(): void
    {
        $this->assertCount(3, Color::rgb(255, 0, 0)->toArray());
        $this->assertCount(4, Color::rgba(255, 0, 0, 128)->toArray());
    }

    public function testColorToHex(): void
    {
        $this->assertSame('#ff0000', Color::red()->toHex());
        $this->assertSame('#ff000080', Color::rgba(255, 0, 0, 128)->toHex());
    }

    public function testColorWithAlpha(): void
    {
        $color = Color::rgb(255, 0, 0)->withAlpha(64);
        $this->assertSame(64, $color->a());
        $this->assertSame(255, $color->r());
    }

    public function testColorValueRangeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Color::rgb(256, 0, 0);
    }

    // -------------------------------------------------------------------------
    // ImageSize
    // -------------------------------------------------------------------------

    public function testImageSize(): void
    {
        $size = new ImageSize(1920, 1080);
        $this->assertSame(1920, $size->width);
        $this->assertSame(1080, $size->height);
        $this->assertSame(1920 * 1080, $size->pixels());
        $this->assertSame('1920x1080', (string) $size);
    }

    public function testImageSizeAspectRatio(): void
    {
        $size = new ImageSize(1600, 900);
        $this->assertSame(16.0 / 9.0, $size->aspectRatio());
    }

    public function testImageSizeAspectRatioZeroHeight(): void
    {
        $size = new ImageSize(100, 0);
        $this->assertSame(0.0, $size->aspectRatio());
    }

    public function testImageSizeScale(): void
    {
        $size = new ImageSize(100, 200);
        $scaled = $size->scale(0.5);
        $this->assertSame(50, $scaled->width);
        $this->assertSame(100, $scaled->height);
    }

    public function testImageSizeWithWidth(): void
    {
        $size = new ImageSize(100, 200);
        $result = $size->withWidth(50);
        $this->assertSame(50, $result->width);
        $this->assertSame(100, $result->height);
    }

    public function testImageSizeWithHeight(): void
    {
        $size = new ImageSize(100, 200);
        $result = $size->withHeight(50);
        $this->assertSame(25, $result->width);
        $this->assertSame(50, $result->height);
    }

    public function testImageSizeContains(): void
    {
        $large = new ImageSize(100, 100);
        $small = new ImageSize(50, 50);
        $this->assertTrue($large->contains($small));
        $this->assertFalse($small->contains($large));
    }

    public function testImageSizeNegativeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ImageSize(-1, 100);
    }

    public function testImageSizeToArray(): void
    {
        $size = new ImageSize(100, 200);
        $this->assertSame(['width' => 100, 'height' => 200], $size->toArray());
    }

    // -------------------------------------------------------------------------
    // BoundingBox
    // -------------------------------------------------------------------------

    public function testBoundingBox(): void
    {
        $box = new BoundingBox(10, 20, 100, 50);
        $this->assertSame(10.0, $box->x);
        $this->assertSame(20.0, $box->y);
        $this->assertSame(100.0, $box->width);
        $this->assertSame(50.0, $box->height);
        $this->assertSame(110.0, $box->x2());
        $this->assertSame(70.0, $box->y2());
        $this->assertSame(60.0, $box->centerX());
        $this->assertSame(45.0, $box->centerY());
        $this->assertSame(5000.0, $box->area());
    }

    public function testBoundingBoxFromCorners(): void
    {
        $box = BoundingBox::fromCorners(10, 20, 110, 70);
        $this->assertSame(10.0, $box->x);
        $this->assertSame(20.0, $box->y);
        $this->assertSame(100.0, $box->width);
        $this->assertSame(50.0, $box->height);
    }

    public function testBoundingBoxFromCenter(): void
    {
        $box = BoundingBox::fromCenter(60, 45, 100, 50);
        $this->assertSame(10.0, $box->x);
        $this->assertSame(20.0, $box->y);
        $this->assertSame(100.0, $box->width);
        $this->assertSame(50.0, $box->height);
    }

    public function testBoundingBoxIou(): void
    {
        $a = new BoundingBox(0, 0, 100, 100);
        $b = new BoundingBox(50, 50, 100, 100);
        $iou = $a->iou($b);
        $this->assertGreaterThan(0, $iou);
        $this->assertLessThan(1, $iou);
    }

    public function testBoundingBoxIouNoOverlap(): void
    {
        $a = new BoundingBox(0, 0, 10, 10);
        $b = new BoundingBox(100, 100, 10, 10);
        $this->assertSame(0.0, $a->iou($b));
    }

    public function testBoundingBoxScale(): void
    {
        $box = new BoundingBox(10, 20, 100, 50);
        $scaled = $box->scale(2.0, 3.0);
        $this->assertSame(20.0, $scaled->x);
        $this->assertSame(60.0, $scaled->y);
        $this->assertSame(200.0, $scaled->width);
        $this->assertSame(150.0, $scaled->height);
    }

    public function testBoundingBoxTranslate(): void
    {
        $box = new BoundingBox(10, 20, 100, 50);
        $translated = $box->translate(5, -10);
        $this->assertSame(15.0, $translated->x);
        $this->assertSame(10.0, $translated->y);
    }

    public function testBoundingBoxExpand(): void
    {
        $box = new BoundingBox(10, 20, 100, 50);
        $expanded = $box->expand(10);
        $this->assertSame(0.0, $expanded->x);
        $this->assertSame(10.0, $expanded->y);
        $this->assertSame(120.0, $expanded->width);
        $this->assertSame(70.0, $expanded->height);
    }

    public function testBoundingBoxClamp(): void
    {
        $box = new BoundingBox(-10, 0, 200, 50);
        $clamped = $box->clamp(100, 100);
        $this->assertSame(0.0, $clamped->x);
        $this->assertSame(0.0, $clamped->y);
        $this->assertSame(100.0, $clamped->width);
        $this->assertSame(50.0, $clamped->height);
    }

    public function testBoundingBoxToInt(): void
    {
        $box = new BoundingBox(10.5, 20.7, 100.3, 50.9);
        $int = $box->toInt();
        $this->assertSame(10.0, $int->x);
        $this->assertSame(20.0, $int->y);
        $this->assertSame(100.0, $int->width);
        $this->assertSame(50.0, $int->height);
    }

    public function testBoundingBoxToArray(): void
    {
        $box = new BoundingBox(10, 20, 100, 50);
        $this->assertSame(['x' => 10.0, 'y' => 20.0, 'width' => 100.0, 'height' => 50.0], $box->toArray());
    }

    public function testBoundingBoxToCornersArray(): void
    {
        $box = new BoundingBox(10, 20, 100, 50);
        $this->assertSame([10.0, 20.0, 110.0, 70.0], $box->toCornersArray());
    }

    public function testBoundingBoxNegativeDimensionsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BoundingBox(0, 0, -100, 50);
    }

    public function testColorTransparentToArray(): void
    {
        $this->assertSame([0, 0, 0, 0], Color::transparent()->toArray());
    }

    public function testColorToHexWithAlpha(): void
    {
        $hex = Color::rgba(0, 255, 0, 0)->toHex();
        $this->assertSame('#00ff0000', $hex);
    }
}
