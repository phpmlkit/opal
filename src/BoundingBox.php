<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Immutable axis-aligned bounding box.
 * All coordinates are in image pixel space (top-left origin).
 */
final readonly class BoundingBox
{
    public function __construct(
        public float $x,
        public float $y,
        public float $width,
        public float $height,
    ) {
        if ($width < 0 || $height < 0) {
            throw new \InvalidArgumentException('Width and height must be non-negative');
        }
    }

    /**
     * Create a bounding box from two corner points.
     *
     * @param float $x1 X-coordinate of the first corner
     * @param float $y1 Y-coordinate of the first corner
     * @param float $x2 X-coordinate of the opposite corner
     * @param float $y2 Y-coordinate of the opposite corner
     */
    public static function fromCorners(float $x1, float $y1, float $x2, float $y2): self
    {
        $x = min($x1, $x2);
        $y = min($y1, $y2);
        $width = abs($x2 - $x1);
        $height = abs($y2 - $y1);

        return new self($x, $y, $width, $height);
    }

    /**
     * Create a bounding box from its center point and dimensions.
     *
     * @param float $cx     Center X-coordinate
     * @param float $cy     Center Y-coordinate
     * @param float $width  Width of the box
     * @param float $height Height of the box
     */
    public static function fromCenter(float $cx, float $cy, float $width, float $height): self
    {
        return new self($cx - $width / 2, $cy - $height / 2, $width, $height);
    }

    /**
     * Get the right edge X-coordinate.
     */
    public function x2(): float
    {
        return $this->x + $this->width;
    }

    /**
     * Get the bottom edge Y-coordinate.
     */
    public function y2(): float
    {
        return $this->y + $this->height;
    }

    /**
     * Get the center X-coordinate.
     */
    public function centerX(): float
    {
        return $this->x + $this->width / 2;
    }

    /**
     * Get the center Y-coordinate.
     */
    public function centerY(): float
    {
        return $this->y + $this->height / 2;
    }

    /**
     * Get the area of the bounding box.
     */
    public function area(): float
    {
        return $this->width * $this->height;
    }

    /**
     * Compute the Intersection over Union (IoU) with another box.
     *
     * @return float IoU value in range [0, 1], or 0 if boxes do not overlap
     */
    public function iou(self $other): float
    {
        $x1 = max($this->x, $other->x);
        $y1 = max($this->y, $other->y);
        $x2 = min($this->x2(), $other->x2());
        $y2 = min($this->y2(), $other->y2());

        if ($x2 <= $x1 || $y2 <= $y1) {
            return 0.0;
        }

        $intersection = ($x2 - $x1) * ($y2 - $y1);
        $union = $this->area() + $other->area() - $intersection;

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Scale the box by the given factors.
     *
     * @param float $scaleX Horizontal scale factor
     * @param float $scaleY Vertical scale factor
     */
    public function scale(float $scaleX, float $scaleY): self
    {
        return new self(
            $this->x * $scaleX,
            $this->y * $scaleY,
            $this->width * $scaleX,
            $this->height * $scaleY
        );
    }

    /**
     * Translate (shift) the box by the given offsets.
     *
     * @param float $dx Horizontal offset
     * @param float $dy Vertical offset
     */
    public function translate(float $dx, float $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy, $this->width, $this->height);
    }

    /**
     * Expand the box outward by a uniform padding amount.
     */
    public function expand(float $padding): self
    {
        return new self(
            $this->x - $padding,
            $this->y - $padding,
            $this->width + 2 * $padding,
            $this->height + 2 * $padding
        );
    }

    /**
     * Clamp the box so it stays within the given image bounds.
     *
     * @param int $imageWidth  Width of the containing image
     * @param int $imageHeight Height of the containing image
     */
    public function clamp(int $imageWidth, int $imageHeight): self
    {
        $x = max(0, min($this->x, $imageWidth));
        $y = max(0, min($this->y, $imageHeight));
        $x2 = max(0, min($this->x2(), $imageWidth));
        $y2 = max(0, min($this->y2(), $imageHeight));

        return new self($x, $y, $x2 - $x, $y2 - $y);
    }

    /**
     * Floor all values to integers.
     */
    public function toInt(): self
    {
        return new self(
            floor($this->x),
            floor($this->y),
            floor($this->width),
            floor($this->height)
        );
    }

    /**
     * @return array{x: float, y: float, width: float, height: float}
     */
    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * @return list<float>
     */
    public function toCornersArray(): array
    {
        return [$this->x, $this->y, $this->x2(), $this->y2()];
    }
}
