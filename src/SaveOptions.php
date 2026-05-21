<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Options controlling how an image is saved. Format-specific.
 * Use one of the static factory methods to get a pre-configured instance.
 */
final readonly class SaveOptions
{
    private function __construct(
        private ?int $quality = null,
        private ?bool $strip = null,
        private ?bool $progressive = null,
        private ?int $compression = null,
        private ?bool $interlace = null,
        private ?bool $lossless = null,
        private ?int $speed = null,
    ) {}

    /**
     * Pre-configured JPEG save options.
     *
     * @param int  $quality     Quality (0-100)
     * @param bool $strip       Strip metadata
     * @param bool $progressive Enable progressive JPEG
     */
    public static function jpeg(
        int $quality = 85,
        bool $strip = true,
        bool $progressive = false,
    ): self {
        return new self($quality, $strip, $progressive);
    }

    /**
     * Pre-configured PNG save options.
     *
     * @param int  $compression Compression level (0-9)
     * @param bool $strip       Strip metadata
     * @param bool $interlace   Enable interlacing
     */
    public static function png(
        int $compression = 6,
        bool $strip = true,
        bool $interlace = false,
    ): self {
        return new self(null, $strip, null, $compression, $interlace);
    }

    /**
     * Pre-configured WebP save options.
     *
     * @param int  $quality  Quality (0-100)
     * @param bool $lossless Use lossless compression
     * @param bool $strip    Strip metadata
     */
    public static function webp(
        int $quality = 80,
        bool $lossless = false,
        bool $strip = true,
    ): self {
        return new self($quality, $strip, null, null, null, $lossless);
    }

    /**
     * Pre-configured TIFF save options.
     *
     * @param int  $quality Quality (0-100)
     * @param bool $strip   Strip metadata
     */
    public static function tiff(int $quality = 75, bool $strip = false): self
    {
        return new self($quality, $strip);
    }

    /**
     * Pre-configured AVIF save options.
     *
     * @param int $quality Quality (0-100)
     * @param int $speed   Encoding speed vs compression trade-off (0-10)
     */
    public static function avif(int $quality = 50, int $speed = 5): self
    {
        return new self($quality, null, null, null, null, null, $speed);
    }

    /**
     * Pre-configured HEIF save options.
     *
     * @param int $quality Quality (0-100)
     */
    public static function heif(int $quality = 50): self
    {
        return new self($quality);
    }

    /**
     * Set the quality (0-100).
     */
    public function withQuality(int $quality): self
    {
        return new self($quality, $this->strip, $this->progressive, $this->compression, $this->interlace, $this->lossless, $this->speed);
    }

    /**
     * Set whether to strip metadata.
     */
    public function withStrip(bool $strip): self
    {
        return new self($this->quality, $strip, $this->progressive, $this->compression, $this->interlace, $this->lossless, $this->speed);
    }

    /**
     * @return array<string, mixed>
     */
    public function toVipsOptions(): array
    {
        $options = [];

        if (null !== $this->quality) {
            $options['Q'] = $this->quality;
        }

        if (null !== $this->strip) {
            $options['strip'] = $this->strip;
        }

        if (null !== $this->progressive) {
            $options['interlace'] = $this->progressive;
        }

        if (null !== $this->compression) {
            $options['compression'] = $this->compression;
        }

        if (null !== $this->interlace) {
            $options['interlace'] = $this->interlace;
        }

        if (null !== $this->lossless) {
            $options['lossless'] = $this->lossless;
        }

        if (null !== $this->speed) {
            $options['speed'] = $this->speed;
        }

        return $options;
    }
}
