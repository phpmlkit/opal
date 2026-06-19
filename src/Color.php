<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Immutable RGBA color value.
 */
final readonly class Color
{
    private function __construct(
        private int $r,
        private int $g,
        private int $b,
        private int $a,
    ) {
        if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255 || $a < 0 || $a > 255) {
            throw new \InvalidArgumentException('Color values must be in range 0-255');
        }
    }

    /**
     * Create an opaque RGB color.
     *
     * @param int $r Red component (0-255)
     * @param int $g Green component (0-255)
     * @param int $b Blue component (0-255)
     */
    public static function rgb(int $r, int $g, int $b): self
    {
        return new self($r, $g, $b, 255);
    }

    /**
     * Create an RGBA color with optional alpha.
     *
     * @param int $r Red component (0-255)
     * @param int $g Green component (0-255)
     * @param int $b Blue component (0-255)
     * @param int $a Alpha component (0-255, default 255)
     */
    public static function rgba(int $r, int $g, int $b, int $a = 255): self
    {
        return new self($r, $g, $b, $a);
    }

    /**
     * Create a gray color from a single intensity value.
     */
    public static function gray(int $value): self
    {
        return new self($value, $value, $value, 255);
    }

    /**
     * Create a color from a hex string.
     *
     * Supported formats: #rgb, #rrggbb, #rrggbbaa
     *
     * @throws ImageException If the hex string is invalid
     */
    public static function fromHex(string $hex): self
    {
        $hex = ltrim($hex, '#');

        $len = \strlen($hex);
        if (!ctype_xdigit($hex)) {
            throw new ImageException('Invalid hex color string');
        }

        if (3 === $len) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
            $a = 255;
        } elseif (6 === $len) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $a = 255;
        } elseif (8 === $len) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $a = hexdec(substr($hex, 6, 2));
        } else {
            throw new \InvalidArgumentException('Invalid hex color format. Expected #rgb, #rrggbb, or #rrggbbaa');
        }

        return new self((int) $r, (int) $g, (int) $b, (int) $a);
    }

    /**
     * Opaque black (#000000).
     */
    public static function black(): self
    {
        return new self(0, 0, 0, 255);
    }

    /**
     * Opaque white (#ffffff).
     */
    public static function white(): self
    {
        return new self(255, 255, 255, 255);
    }

    /**
     * Fully transparent black (rgba(0, 0, 0, 0)).
     */
    public static function transparent(): self
    {
        return new self(0, 0, 0, 0);
    }

    /**
     * Opaque red (#ff0000).
     */
    public static function red(): self
    {
        return new self(255, 0, 0, 255);
    }

    /**
     * Opaque green (#00ff00).
     */
    public static function green(): self
    {
        return new self(0, 255, 0, 255);
    }

    /**
     * Opaque blue (#0000ff).
     */
    public static function blue(): self
    {
        return new self(0, 0, 255, 255);
    }

    /**
     * Get the red component.
     */
    public function r(): int
    {
        return $this->r;
    }

    /**
     * Get the green component.
     */
    public function g(): int
    {
        return $this->g;
    }

    /**
     * Get the blue component.
     */
    public function b(): int
    {
        return $this->b;
    }

    /**
     * Get the alpha component.
     */
    public function a(): int
    {
        return $this->a;
    }

    /**
     * Whether this color carries an alpha (transparency) component.
     */
    public function hasAlpha(): bool
    {
        return $this->a < 255;
    }

    /**
     * Return the color as an array sized to match a given number of bands.
     *
     * - 1 band  → [luma]  (ITU-R BT.601 luma of the RGB components)
     * - 3 bands → [R, G, B]  (alpha is dropped)
     * - 4 bands → [R, G, B, A]
     *
     * The band count must be supplied by the caller because the decision depends
     * on the destination image (or operation), not on the color's own alpha value.
     * Defaults to 3 for backward compatibility with external callers.
     *
     * @param int $bands Number of bands the result must contain (1, 3, or 4)
     *
     * @return list<int>
     *
     * @throws \InvalidArgumentException If $bands is not 1, 3, or 4
     */
    public function toArray(int $bands = 3): array
    {
        return match ($bands) {
            1 => [(int) round(0.299 * $this->r + 0.587 * $this->g + 0.114 * $this->b)],
            3 => [$this->r, $this->g, $this->b],
            4 => [$this->r, $this->g, $this->b, $this->a],
            default => throw new \InvalidArgumentException(
                \sprintf('Color::toArray() bands must be 1, 3, or 4, got %d', $bands)
            ),
        };
    }

    /**
     * Float-valued variant of toArray() for libvips draw operations.
     *
     * @param int $bands Number of bands the result must contain (1, 3, or 4)
     *
     * @return list<float>
     *
     * @throws \InvalidArgumentException If $bands is not 1, 3, or 4
     */
    public function toFloatArray(int $bands = 3): array
    {
        return array_map('floatval', $this->toArray($bands));
    }

    /**
     * Get the hex string representation.
     *
     * @return string e.g. "#ff0000" or "#ff000080" if alpha < 255
     */
    public function toHex(): string
    {
        if ($this->a < 255) {
            return \sprintf('#%02x%02x%02x%02x', $this->r, $this->g, $this->b, $this->a);
        }

        return \sprintf('#%02x%02x%02x', $this->r, $this->g, $this->b);
    }

    /**
     * Create a new color with the given alpha, preserving RGB values.
     */
    public function withAlpha(int $alpha): self
    {
        return new self($this->r, $this->g, $this->b, $alpha);
    }
}
