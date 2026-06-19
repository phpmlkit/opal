<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Supported color spaces. Backed by libvips interpretation strings.
 *
 * Each case corresponds to a libvips interpretation. Channel count and pixel
 * transformation behavior is handled by the Image conversion methods, not by
 * the enum itself.
 */
enum ColorSpace: string
{
    case RGB = 'srgb';
    case Grayscale = 'b-w';
    case Lab = 'lab';
    case HSV = 'hsv';
    case CMYK = 'cmyk';
    case Oklab = 'oklab';

    public static function fromVipsInterpretation(string $interpretation): ColorSpace
    {
        return match ($interpretation) {
            'srgb', 'rgb', 'scrgb' => self::RGB,
            'b-w', 'grey' => self::Grayscale,
            'lab', 'labs', 'labq' => self::Lab,
            'hsv' => self::HSV,
            'cmyk' => self::CMYK,
            'oklab' => self::Oklab,
            default => self::RGB,
        };
    }

    public function toVipsInterpretation(): string
    {
        return $this->value;
    }

    /**
     * Number of channels in this color space (without alpha).
     *
     * Used to determine the band count when creating new images in this space.
     * Note that this is the space's intrinsic band count; alpha (if any) is
     * tracked separately as an extra band.
     */
    public function bands(): int
    {
        return match ($this) {
            self::Grayscale => 1,
            self::CMYK => 4,
            self::RGB, self::Lab, self::HSV, self::Oklab => 3,
        };
    }
}
