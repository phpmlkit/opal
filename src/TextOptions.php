<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Options controlling how text is rendered.
 *
 * Immutable — use the with* methods to derive variants.
 */
final readonly class TextOptions
{
    public const DEFAULT_FONT_FAMILY = 'sans-serif';

    public const DEFAULT_FONT_SIZE = 12;

    public function __construct(
        public ?string $fontFamily = null,
        public ?string $fontFile = null,
        public ?int $fontSize = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $align = null,
        public ?bool $justify = null,
        public ?int $dpi = null,
        public ?bool $rgba = null,
        public ?int $spacing = null,
        public ?string $wrap = null,
    ) {}

    /**
     * Default text options (no overrides; uses the renderer's defaults).
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Set the font family, and optionally a path to a font file for it.
     *
     * Without `$fontFile` the family is resolved from the system's installed
     * fonts. With a file path, the family should match the font's real family
     * name (not the filename) — extract it with `fc-query` if unsure. Calling
     * this method clears any previously set file path.
     */
    public function withFont(string $fontFamily, ?string $fontFile = null): self
    {
        return new self($fontFamily, $fontFile, $this->fontSize, $this->width, $this->height, $this->align, $this->justify, $this->dpi, $this->rgba, $this->spacing, $this->wrap);
    }

    /**
     * Set the maximum text block width in pixels.
     */
    public function withWidth(int $width): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $width, $this->height, $this->align, $this->justify, $this->dpi, $this->rgba, $this->spacing, $this->wrap);
    }

    /**
     * Set the maximum text block height in pixels.
     */
    public function withHeight(int $height): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $this->width, $height, $this->align, $this->justify, $this->dpi, $this->rgba, $this->spacing, $this->wrap);
    }

    /**
     * Set alignment: "low" (left), "centre" (center), or "high" (right).
     */
    public function withAlign(string $align): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $this->width, $this->height, $align, $this->justify, $this->dpi, $this->rgba, $this->spacing, $this->wrap);
    }

    /**
     * Enable or disable text justification.
     */
    public function withJustify(bool $justify): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $this->width, $this->height, $this->align, $justify, $this->dpi, $this->rgba, $this->spacing, $this->wrap);
    }

    /**
     * Set the font size in points.
     */
    public function withFontSize(int $fontSize): self
    {
        return new self($this->fontFamily, $this->fontFile, $fontSize, $this->width, $this->height, $this->align, $this->justify, $this->dpi, $this->rgba, $this->spacing, $this->wrap);
    }

    /**
     * Set rendering DPI (default 72).
     */
    public function withDpi(int $dpi): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $this->width, $this->height, $this->align, $this->justify, $dpi, $this->rgba, $this->spacing, $this->wrap);
    }

    /**
     * Enable RGBA rendering (for transparent backgrounds).
     */
    public function withRgba(bool $rgba = true): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $this->width, $this->height, $this->align, $this->justify, $this->dpi, $rgba, $this->spacing, $this->wrap);
    }

    /**
     * Set line spacing in points.
     */
    public function withSpacing(int $spacing): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $this->width, $this->height, $this->align, $this->justify, $this->dpi, $this->rgba, $spacing, $this->wrap);
    }

    /**
     * Set text wrapping: "word", "char", "word-char", or "none".
     */
    public function withWrap(string $wrap): self
    {
        return new self($this->fontFamily, $this->fontFile, $this->fontSize, $this->width, $this->height, $this->align, $this->justify, $this->dpi, $this->rgba, $this->spacing, $wrap);
    }

    /**
     * Compose these options into a dictionary suitable for the underlying
     * renderer.
     *
     * @return array<string, mixed>
     */
    public function toVipsOptions(): array
    {
        $vipsOptions = [];

        if (null !== $this->fontFamily || null !== $this->fontSize || null !== $this->fontFile) {
            $family = $this->fontFamily ?? self::DEFAULT_FONT_FAMILY;
            $size = $this->fontSize ?? self::DEFAULT_FONT_SIZE;
            $vipsOptions['font'] = "{$family} {$size}";
        }

        if (null !== $this->fontFile) {
            $vipsOptions['fontfile'] = $this->fontFile;
        }

        if (null !== $this->width) {
            $vipsOptions['width'] = $this->width;
        }

        if (null !== $this->height) {
            $vipsOptions['height'] = $this->height;
        }

        if (null !== $this->align) {
            $vipsOptions['align'] = $this->align;
        }

        if (null !== $this->justify) {
            $vipsOptions['justify'] = $this->justify;
        }

        if (null !== $this->dpi) {
            $vipsOptions['dpi'] = $this->dpi;
        }

        if (null !== $this->rgba) {
            $vipsOptions['rgba'] = $this->rgba;
        }

        if (null !== $this->spacing) {
            $vipsOptions['spacing'] = $this->spacing;
        }

        if (null !== $this->wrap) {
            $vipsOptions['wrap'] = $this->wrap;
        }

        return $vipsOptions;
    }
}
