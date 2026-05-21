<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ImageException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageBandTest extends TestCase
{
    public function testGetSingleBand(): void
    {
        $image = Image::blank(10, 10);
        $band = $image->get(0);
        $this->assertSame(10, $band->width());
        $this->assertSame(10, $band->height());
        $this->assertSame(1, $band->bands());
    }

    public function testGetInvalidIndexThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Image::blank(10, 10)->get(5);
    }

    public function testGetNegativeIndexThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Image::blank(10, 10)->get(-1);
    }

    public function testSplit(): void
    {
        $image = Image::blank(10, 10);
        $bands = $image->split();
        $this->assertCount(3, $bands);
        foreach ($bands as $band) {
            $this->assertSame(10, $band->width());
            $this->assertSame(10, $band->height());
            $this->assertSame(1, $band->bands());
        }
    }

    public function testMerge(): void
    {
        $image = Image::blank(10, 10);
        $bands = $image->split();
        $merged = Image::merge($bands);
        $this->assertSame(10, $merged->width());
        $this->assertSame(10, $merged->height());
        $this->assertSame(3, $merged->bands());
    }

    public function testMergeEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Image::merge([]);
    }

    public function testMergeInvalidElementThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // @phpstan-ignore-next-line intentionally invalid type
        Image::merge(['not-an-image']);
    }

    public function testReorder(): void
    {
        $image = Image::blank(10, 10);
        $reordered = $image->reorder([2, 1, 0]);
        $this->assertSame(10, $reordered->width());
        $this->assertSame(3, $reordered->bands());
    }

    public function testReorderInvalidIndexThrows(): void
    {
        $this->expectException(ImageException::class);
        Image::blank(10, 10)->reorder([0, 1, 99]);
    }

    public function testSplitMergeRoundTrip(): void
    {
        $image = Image::blank(10, 10);
        $bands = $image->split();
        $merged = Image::merge($bands);
        $this->assertSame($image->width(), $merged->width());
        $this->assertSame($image->height(), $merged->height());
        $this->assertSame($image->bands(), $merged->bands());
    }
}
