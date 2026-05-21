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
     * @return list<int> Either [R, G, B] or [R, G, B, A] when transparency is present
     */
    public function toArray(): array
    {
        return $this->a < 255 ? [$this->r, $this->g, $this->b, $this->a] : [$this->r, $this->g, $this->b];
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
