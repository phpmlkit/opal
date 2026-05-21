<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Supported color spaces. Backed by vips interpretation strings.
 */
enum ColorSpace: string
{
    case RGB = 'rgb';
    case RGBA = 'rgba';
    case BGR = 'bgr';
    case BGRA = 'bgra';
    case Grayscale = 'grey';
    case Lab = 'lab';
    case HSV = 'hsv';
    case CMYK = 'cmyk';

    /**
     * Number of channels for a newly created image in this space.
     */
    public function bands(): int
    {
        return match ($this) {
            self::Grayscale => 1,
            self::RGBA, self::BGRA => 4,
            self::CMYK => 4,
            default => 3,
        };
    }

    public static function fromVipsInterpretation(string $interpretation): ColorSpace
    {
        return match ($interpretation) {
            'rgb', 'srgb', 'scrgb' => self::RGB,
            'rgba' => self::RGBA,
            'bgr' => self::BGR,
            'bgra' => self::BGRA,
            'grey', 'b-w' => self::Grayscale,
            'lab', 'labs', 'labq' => self::Lab,
            'hsv' => self::HSV,
            'cmyk' => self::CMYK,
            default => self::RGB,
        };
    }

    public function toVipsInterpretation(): string
    {
        return match ($this) {
            ColorSpace::RGB => 'srgb',
            ColorSpace::RGBA => 'srgb',
            ColorSpace::BGR => 'bgr',
            ColorSpace::BGRA => 'bgr',
            ColorSpace::Grayscale => 'b-w',
            ColorSpace::Lab => 'lab',
            ColorSpace::HSV => 'hsv',
            ColorSpace::CMYK => 'cmyk',
        };
    }
}
