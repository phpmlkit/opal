<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Internal;

use FFI\CData;
use Jcupitt\Vips\FFI as VipsFFI;
use Jcupitt\Vips\Image as VipsImage;
use PhpMlKit\Opal\BandFormat;
use PhpMlKit\Opal\ImageException;

/**
 * Internal factory for creating VIPS images from raw memory data.
 *
 * This class exists to wrap the libvips vips_image_new_from_memory_copy function
 * with proper size calculation, avoiding the need to use strlen() on PHP strings.
 * It accepts raw buffer data (FFI\CData) along with explicit dimensions and
 * band format to create a VIPS image safely.
 *
 * @internal
 */
final class VipsImageFactory
{
    /**
     * Create a new VIPS image from raw memory data.
     *
     * @param CData      $data       Pointer to pixel bytes
     * @param int        $width      Image width in pixels
     * @param int        $height     Image height in pixels
     * @param int        $bands      Number of color bands (e.g., 3 for RGB, 4 for RGBA)
     * @param BandFormat $bandFormat Data type of each band
     *
     * @return VipsImage Newly created VIPS image instance
     *
     * @throws ImageException If the image creation fails
     */
    public static function newFromMemory(
        CData $data,
        int $width,
        int $height,
        int $bands,
        BandFormat $bandFormat,
    ): VipsImage {
        $size = $width * $height * $bands * $bandFormat->storageBytes();

        /** @phpstan-ignore-next-line FFI dynamic method */
        $ptr = VipsFFI::vips()->vips_image_new_from_memory_copy(
            $data,
            $size,
            $width,
            $height,
            $bands,
            $bandFormat->value,
        );

        if (null === $ptr) {
            throw new ImageException('Failed to create image from memory');
        }

        return new VipsImage($ptr);
    }
}
