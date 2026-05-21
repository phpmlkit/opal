<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\NDArray\DType;
use PhpMlKit\NDArray\NDArray;
use PhpMlKit\Opal\BandFormat;
use PhpMlKit\Opal\ChannelFormat;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Exceptions\ShapeException;
use PhpMlKit\Opal\Image;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageArrayTest extends TestCase
{
    // -------------------------------------------------------------------------
    // fromArray — HWC
    // -------------------------------------------------------------------------

    public function testFromArrayHwcInfersShape(): void
    {
        $data = [
            [[255, 0, 0], [0, 255, 0]],
            [[0, 0, 255], [128, 128, 128]],
        ];
        $image = Image::fromArray($data);
        $this->assertSame(2, $image->height());
        $this->assertSame(2, $image->width());
        $this->assertSame(3, $image->bands());
        $this->assertSame(BandFormat::UCHAR, $image->bandFormat());
    }

    // -------------------------------------------------------------------------
    // fromArray — CHW
    // -------------------------------------------------------------------------

    public function testFromArrayChwInfersShape(): void
    {
        $data = [
            [[255, 0], [0, 255]],
            [[0, 255], [255, 0]],
            [[128, 128], [64, 64]],
        ];
        $image = Image::fromArray($data, ChannelFormat::CHW);
        $this->assertSame(2, $image->height());
        $this->assertSame(2, $image->width());
        $this->assertSame(3, $image->bands());
    }

    public function testFromArrayToArrayHwcRoundTrip(): void
    {
        $original = [
            [[255, 0, 0], [0, 255, 0]],
            [[0, 0, 255], [128, 128, 128]],
        ];
        $image = Image::fromArray($original);
        $exported = $image->toArray();
        $this->assertSame($original, $exported->toArray());
    }

    public function testFromArrayToArrayChwRoundTrip(): void
    {
        $original = [
            [[255, 0], [0, 255]],
            [[0, 255], [255, 0]],
            [[128, 128], [64, 64]],
        ];
        $image = Image::fromArray($original, ChannelFormat::CHW);
        $exported = $image->toArray(ChannelFormat::CHW);
        $this->assertSame($original, $exported->toArray());
    }

    public function testFromArrayToArrayGrayscaleRoundTrip(): void
    {
        $original = [
            [[0], [128]],
            [[255], [64]],
        ];
        $image = Image::fromArray($original, colorSpace: ColorSpace::Grayscale);
        $exported = $image->toArray();
        $this->assertSame($original, $exported->toArray());
    }

    public function testFromArrayAcceptsNDArray(): void
    {
        $nd = NDArray::ones([1, 2, 3], DType::UInt8);
        $image = Image::fromArray($nd);
        $this->assertSame(1, $image->height());
        $this->assertSame(2, $image->width());
        $this->assertSame(3, $image->bands());
    }

    // -------------------------------------------------------------------------
    // fromArray — validation
    // -------------------------------------------------------------------------

    public function testFromArrayRejectsEmpty(): void
    {
        $this->expectException(ShapeException::class);
        Image::fromArray([]);
    }

    public function testFromArrayRejects1D(): void
    {
        $this->expectException(ShapeException::class);
        // @phpstan-ignore-next-line intentionally invalid 1D input
        Image::fromArray([1, 2, 3]);
    }

    public function testFromArrayRejectsBadBands(): void
    {
        $this->expectException(ShapeException::class);
        Image::fromArray([
            [[1, 2, 3, 4, 5], [1, 2, 3, 4, 5]],
            [[1, 2, 3, 4, 5], [1, 2, 3, 4, 5]],
        ]);
    }
}
