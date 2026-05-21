<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Options controlling how an image is loaded from disk or buffer.
 * Immutable — use with* methods to derive variants.
 */
final readonly class LoadOptions
{
    public function __construct(
        public ?int $page = null,
        public ?int $n = null,
        public bool $autoRotate = true,
        public ?int $shrink = null,
        public ?float $scale = null,
    ) {}

    /**
     * Default load options with auto-rotation enabled.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Set the page (zero-based) to load for multi-page formats (e.g. TIFF, PDF).
     */
    public function withPage(int $page): self
    {
        return new self($page, $this->n, $this->autoRotate, $this->shrink, $this->scale);
    }

    /**
     * Set the number of pages to load (for multi-page formats).
     */
    public function withN(int $n): self
    {
        return new self($this->page, $n, $this->autoRotate, $this->shrink, $this->scale);
    }

    /**
     * Enable or disable auto-rotation based on EXIF orientation.
     */
    public function withAutoRotate(bool $autoRotate): self
    {
        return new self($this->page, $this->n, $autoRotate, $this->shrink, $this->scale);
    }

    /**
     * Set the shrink-on-load factor for large images (prefer this over post-load resize).
     */
    public function withShrink(int $factor): self
    {
        return new self($this->page, $this->n, $this->autoRotate, $factor, $this->scale);
    }

    /**
     * Set the scale factor (applied during decode, faster than post-load resize).
     */
    public function withScale(float $scale): self
    {
        return new self($this->page, $this->n, $this->autoRotate, $this->shrink, $scale);
    }

    /**
     * @return array<string, mixed>
     */
    public function toVipsOptions(): array
    {
        $vipsOptions = [];

        if (null !== $this->page) {
            $vipsOptions['page'] = $this->page;
        }

        if (null !== $this->n) {
            $vipsOptions['n'] = $this->n;
        }

        if (!$this->autoRotate) {
            $vipsOptions['autorotate'] = false;
        }

        if (null !== $this->shrink) {
            $vipsOptions['shrink'] = $this->shrink;
        }

        if (null !== $this->scale) {
            $vipsOptions['scale'] = $this->scale;
        }

        return $vipsOptions;
    }
}
