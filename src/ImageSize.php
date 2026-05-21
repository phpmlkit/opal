<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Immutable 2-D size descriptor.
 */
final readonly class ImageSize
{
    public function __construct(
        public int $width,
        public int $height,
    ) {
        if ($width < 0 || $height < 0) {
            throw new \InvalidArgumentException('Width and height must be non-negative');
        }
    }

    /**
     * Format as "WxH", e.g. "1920x1080".
     */
    public function __toString(): string
    {
        return "{$this->width}x{$this->height}";
    }

    /**
     * Get the aspect ratio (width / height).
     *
     * @return float Returns 0 if height is 0
     */
    public function aspectRatio(): float
    {
        return $this->height > 0 ? $this->width / $this->height : 0;
    }

    /**
     * Get the total pixel count (width × height).
     */
    public function pixels(): int
    {
        return $this->width * $this->height;
    }

    /**
     * Derive a new size with the given width, preserving aspect ratio.
     */
    public function withWidth(int $width): self
    {
        $scale = $width / $this->width;

        return new self($width, (int) round($this->height * $scale));
    }

    /**
     * Derive a new size with the given height, preserving aspect ratio.
     */
    public function withHeight(int $height): self
    {
        $scale = $height / $this->height;

        return new self((int) round($this->width * $scale), $height);
    }

    /**
     * Scale both dimensions by a uniform factor.
     */
    public function scale(float $factor): self
    {
        return new self(
            (int) round($this->width * $factor),
            (int) round($this->height * $factor)
        );
    }

    /**
     * Check whether this size fully contains the other size.
     */
    public function contains(self $other): bool
    {
        return $this->width >= $other->width && $this->height >= $other->height;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function toArray(): array
    {
        return ['width' => $this->width, 'height' => $this->height];
    }
}
