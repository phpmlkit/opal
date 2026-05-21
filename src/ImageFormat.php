<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

use PhpMlKit\Opal\Exceptions\UnsupportedFormatException;

/**
 * Image file container formats.
 */
enum ImageFormat: string
{
    case JPEG = 'jpeg';
    case PNG = 'png';
    case WebP = 'webp';
    case TIFF = 'tiff';
    case GIF = 'gif';
    case BMP = 'bmp';
    case AVIF = 'avif';
    case HEIF = 'heif';

    public static function fromExtension(string $extension): ImageFormat
    {
        return match ($extension) {
            'jpg', 'jpeg' => ImageFormat::JPEG,
            'png' => ImageFormat::PNG,
            'webp' => ImageFormat::WebP,
            'tif', 'tiff' => ImageFormat::TIFF,
            'gif' => ImageFormat::GIF,
            'bmp' => ImageFormat::BMP,
            'avif' => ImageFormat::AVIF,
            'heif', 'heic' => ImageFormat::HEIF,
            default => throw new UnsupportedFormatException("Unsupported image format: {$extension}"),
        };
    }

    public function toExtension(): string
    {
        return match ($this) {
            ImageFormat::JPEG => 'jpg',
            ImageFormat::PNG => 'png',
            ImageFormat::WebP => 'webp',
            ImageFormat::TIFF => 'tif',
            ImageFormat::GIF => 'gif',
            ImageFormat::BMP => 'bmp',
            ImageFormat::AVIF => 'avif',
            ImageFormat::HEIF => 'heif',
        };
    }

    /**
     * Returns the file suffix for the format (including the leading dot).
     */
    public function suffix(): string
    {
        return match ($this) {
            ImageFormat::JPEG => '.jpg',
            ImageFormat::PNG => '.png',
            ImageFormat::WebP => '.webp',
            ImageFormat::TIFF => '.tif',
            ImageFormat::GIF => '.gif',
            ImageFormat::BMP => '.bmp',
            ImageFormat::AVIF => '.avif',
            ImageFormat::HEIF => '.heif',
        };
    }
}
