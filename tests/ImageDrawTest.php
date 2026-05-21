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
}
