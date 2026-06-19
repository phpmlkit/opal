<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

use FFI\CData;
use Jcupitt\Vips\Angle;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use Jcupitt\Vips\VipsOperation;
use PhpMlKit\NDArray\DType;
use PhpMlKit\NDArray\Exceptions\IndexException;
use PhpMlKit\NDArray\NDArray;
use PhpMlKit\Opal\Exceptions\FileNotFoundException;
use PhpMlKit\Opal\Exceptions\InvalidImageException;
use PhpMlKit\Opal\Exceptions\ShapeException;
use PhpMlKit\Opal\Internal\VipsImageFactory;

/**
 * High-performance image backed by a libvips via FFI.
 *
 * All transformation methods are immutable and return a new Image.
 * Computation is lazy — vips pipelines are only executed when the image
 * data is actually consumed (toArray, toBuffer, toFile, toMemory).
 */
final readonly class Image
{
    public function __construct(public VipsImage $vipsImage) {}

    // -------------------------------------------------------------------------
    // Factory / Loading
    // -------------------------------------------------------------------------

    /**
     * Load an image from a file path.
     * Format is detected automatically from the file header.
     *
     * @param string           $path    Path to the image file
     * @param null|LoadOptions $options Optional loading options
     *
     * @return self New Image instance
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws InvalidImageException If the file cannot be loaded as an image
     */
    public static function fromFile(string $path, ?LoadOptions $options = null): self
    {
        if (!is_file($path)) {
            throw new FileNotFoundException("File not found: {$path}");
        }

        $options ??= LoadOptions::default();

        try {
            $vipsImage = VipsImage::newFromFile($path, $options->toVipsOptions());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw InvalidImageException::wrap("Failed to load image from file: {$path}", $e);
        }
    }

    /**
     * Decode an image from a binary string (e.g. from file_get_contents or HTTP response).
     *
     * @param string           $buffer  Binary image data
     * @param null|LoadOptions $options Optional loading options
     *
     * @return self New Image instance
     *
     * @throws InvalidImageException If the buffer cannot be loaded as an image
     */
    public static function fromBuffer(string $buffer, ?LoadOptions $options = null): self
    {
        $options ??= LoadOptions::default();

        try {
            $vipsImage = VipsImage::newFromBuffer($buffer, '', $options->toVipsOptions());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw InvalidImageException::wrap('Failed to load image from buffer', $e);
        }
    }

    /**
     * Wrap a raw pixel buffer already in memory.
     * Note: This method creates a copy of the buffer data.
     *
     * @param CData      $buffer     Raw pixel buffer
     * @param int        $width      Image width in pixels
     * @param int        $height     Image height in pixels
     * @param int        $bands      Number of color bands (e.g., 3 for RGB, 4 for RGBA)
     * @param BandFormat $bandFormat Data type of each band (default: UCHAR)
     * @param ColorSpace $colorSpace Color space of the buffer data
     *
     * @return self New Image instance
     *
     * @throws InvalidImageException If the image cannot be created from memory
     */
    public static function fromMemory(
        CData $buffer,
        int $width,
        int $height,
        int $bands,
        BandFormat $bandFormat = BandFormat::UCHAR,
        ColorSpace $colorSpace = ColorSpace::RGB,
    ): self {
        $vipsInterpretation = $colorSpace->toVipsInterpretation();

        try {
            $vipsImage = VipsImageFactory::newFromMemory($buffer, $width, $height, $bands, $bandFormat);
            $vipsImage = $vipsImage->copy(['interpretation' => $vipsInterpretation]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw InvalidImageException::wrap('Failed to create image from memory', $e);
        }
    }

    /**
     * Create an Image from a PHP array or NDArray.
     *
     * Expected layout:
     *   ChannelFormat::HWC  →  [H, W, C]  (TensorFlow / numpy default)
     *   ChannelFormat::CHW  →  [C, H, W]  (PyTorch default)
     *
     * For NDArray, the buffer is copied into a new Image.
     *
     * @param array<array<array<int>>>|NDArray $array         Input pixel data as array or NDArray
     * @param ColorSpace                       $colorSpace    Color space of the image data (default: RGB)
     * @param ChannelFormat                    $channelFormat Format of the array data (default: HWC)
     *
     * @return self New Image instance
     *
     * @throws IndexException
     * @throws \PhpMlKit\NDArray\Exceptions\ShapeException
     * @throws ShapeException
     */
    public static function fromArray(
        array|NDArray $array,
        ChannelFormat $channelFormat = ChannelFormat::HWC,
        ColorSpace $colorSpace = ColorSpace::RGB,
    ): self {
        if (\is_array($array)) {
            if (empty($array)) {
                throw new ShapeException('Array is empty');
            }
            $array = NDArray::array($array, DType::UInt8);
        }

        if (3 !== $array->ndim()) {
            throw new ShapeException('array shape must have 3 dimensions, got '.$array->ndim());
        }

        if (ChannelFormat::CHW === $channelFormat) {
            $array = $array->permute(1, 2, 0);
        }

        $shape = $array->shape();

        [$height, $width, $bands] = $shape;

        if ($bands < 1 || $bands > 4) {
            throw new ShapeException('Channel dimension must be between 1 and 4');
        }

        $bandFormat = BandFormat::fromDtype($array->dtype());

        return self::fromMemory($array->toBuffer(), $width, $height, $bands, $bandFormat, $colorSpace);
    }

    /**
     * Create a blank image filled with $background color (or black/transparent).
     *
     * @param int        $width      Width of the blank image in pixels
     * @param int        $height     Height of the blank image in pixels
     * @param ColorSpace $colorSpace Color space of the image (default: RGB)
     * @param null|Color $background Background color to fill with (null for black/transparent)
     *
     * @return self New blank Image instance
     *
     * @throws InvalidImageException If the blank image cannot be created
     */
    public static function blank(
        int $width,
        int $height,
        ?Color $background = null,
        ColorSpace $colorSpace = ColorSpace::RGB,
    ): self {
        $bands = $colorSpace->bands();

        try {
            $vipsImage = VipsImage::black($width, $height);

            if ($bands > 1) {
                $vipsImage = $vipsImage->bandjoin(array_fill(0, $bands - 1, $vipsImage));
            }

            if (null !== $background) {
                $vipsImage = $vipsImage->newFromImage($background->toArray($bands));
            }

            $vipsImage = $vipsImage->copy(['interpretation' => $colorSpace->toVipsInterpretation()]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw InvalidImageException::wrap('Failed to create blank image', $e);
        }
    }

    /**
     * Create a text image.
     *
     * @param string           $text    The text to render
     * @param null|TextOptions $options Optional text rendering options
     *
     * @return self New image containing the text
     *
     * @throws ImageException If the text image creation fails
     */
    public static function text(string $text, ?TextOptions $options = null): self
    {
        $options ??= TextOptions::default();

        try {
            return new self(VipsImage::text($text, $options->toVipsOptions()));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to create text image', $e);
        }
    }

    /**
     * Create a thumbnail from a file using shrink-on-load.
     *
     * This is faster and uses less memory than loading the full image then resizing —
     * it exploits the shrink-at-decode features built into formats such as JPEG,
     * WebP and TIFF so that only the data needed for the target size is decoded.
     *
     * @param string   $filename Path to the image file
     * @param int      $width    Target width in pixels
     * @param null|int $height   Optional target height. When omitted the aspect ratio
     *                           is determined by width alone.
     * @param bool     $noRotate Whether to skip auto-rotation based on EXIF orientation
     * @param bool     $linear   Whether to perform the shrink in linear light
     *
     * @return self New Image instance containing the thumbnail
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws InvalidImageException If the file cannot be thumbnailed
     */
    public static function thumbnail(
        string $filename,
        int $width,
        ?int $height = null,
        bool $noRotate = false,
        bool $linear = false,
    ): self {
        if (!is_file($filename)) {
            throw new FileNotFoundException("File not found: {$filename}");
        }

        try {
            $options = [];
            if (null !== $height) {
                $options['height'] = $height;
            }
            if ($noRotate) {
                $options['no_rotate'] = true;
            }
            if ($linear) {
                $options['linear'] = true;
            }

            return new self(VipsImage::thumbnail($filename, $width, $options));
        } catch (\Exception $e) {
            throw InvalidImageException::wrap("Failed to create thumbnail from file: {$filename}", $e);
        }
    }

    // -------------------------------------------------------------------------
    // Metadata / Inspection
    // -------------------------------------------------------------------------

    /**
     * Get the width of the image in pixels.
     *
     * @return int Image width in pixels
     */
    public function width(): int
    {
        return $this->vipsImage->width;
    }

    /**
     * Get the height of the image in pixels.
     *
     * @return int Image height in pixels
     */
    public function height(): int
    {
        return $this->vipsImage->height;
    }

    /**
     * Get the number of color bands in the image.
     *
     * @return int Number of bands (e.g., 1 for grayscale, 3 for RGB, 4 for RGBA)
     */
    public function bands(): int
    {
        return $this->vipsImage->bands;
    }

    /**
     * Get the size of the image as an ImageSize object.
     *
     * @return ImageSize Object containing width and height
     */
    public function size(): ImageSize
    {
        return new ImageSize($this->width(), $this->height());
    }

    /**
     * Get the color space of the image.
     *
     * @return ColorSpace The color space of the image
     */
    public function colorSpace(): ColorSpace
    {
        return ColorSpace::fromVipsInterpretation($this->vipsImage->interpretation);
    }

    /**
     * Get the band format (data type) of the image.
     *
     * @return BandFormat The data type used for each band
     */
    public function bandFormat(): BandFormat
    {
        return BandFormat::fromString($this->vipsImage->format);
    }

    /**
     * Check if the image has an alpha (transparency) channel.
     *
     * @return bool True if the image has an alpha channel, false otherwise
     */
    public function hasAlpha(): bool
    {
        return $this->vipsImage->hasAlpha();
    }

    /**
     * Get the number of pages in the image (for multipage formats like TIFF).
     *
     * @return int Number of pages in the image (default 1 for single-page images)
     */
    public function pageCount(): int
    {
        try {
            return $this->vipsImage->get('n_pages');
        } catch (\Exception) {
            return 1;
        }
    }

    /**
     * Get the resolution of the image in DPI (dots per inch).
     *
     * @return array{x: float, y: float} Associative array with 'x' and 'y' keys
     */
    public function resolution(): array
    {
        return ['x' => $this->vipsImage->xres, 'y' => $this->vipsImage->yres];
    }

    /**
     * Get EXIF metadata from the image.
     *
     * @return array<string, mixed> Associative array of EXIF data (empty array if no EXIF data present)
     */
    public function exif(): array
    {
        try {
            return $this->vipsImage->get('exif-data') ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Get the ICC profile data from the image.
     *
     * @return null|string The ICC profile data as a string, or null if no profile is present
     */
    public function iccProfile(): ?string
    {
        try {
            return $this->vipsImage->get('icc-profile-data');
        } catch (\Exception) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Color Space Conversion
    // -------------------------------------------------------------------------

    /**
     * Convert the image to sRGB color space.
     *
     * Always returns a 3-band image: alpha (if present) is dropped, and pixels
     * are converted from the source interpretation. Idempotent if the image is
     * already a 3-band sRGB image.
     *
     * @return self New 3-band sRGB Image instance
     *
     * @throws ImageException If the conversion fails
     */
    public function toRGB(): self
    {
        if (3 === $this->bands()
            && !$this->hasAlpha()
            && ColorSpace::RGB === $this->colorSpace()
        ) {
            return $this;
        }

        try {
            $vipsImage = $this->stripAlpha()->colourspace(ColorSpace::RGB->toVipsInterpretation());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to convert to RGB', $e);
        }
    }

    /**
     * Convert the image to grayscale.
     *
     * Always returns a 1-band image: alpha (if present) is dropped, and pixels
     * are converted to luma. Idempotent if the image is already 1-band b-w.
     *
     * @return self New 1-band grayscale Image instance
     *
     * @throws ImageException If the conversion fails
     */
    public function toGrayscale(): self
    {
        if (1 === $this->bands()
            && !$this->hasAlpha()
            && ColorSpace::Grayscale === $this->colorSpace()
        ) {
            return $this;
        }

        try {
            $vipsImage = $this->stripAlpha()->colourspace(ColorSpace::Grayscale->toVipsInterpretation());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to convert to grayscale', $e);
        }
    }

    /**
     * Convert the image to CIE Lab color space.
     *
     * Always returns a 3-band float image: alpha (if present) is dropped.
     *
     * @return self New 3-band Lab Image instance
     *
     * @throws ImageException If the conversion fails
     */
    public function toLab(): self
    {
        if (3 === $this->bands()
            && !$this->hasAlpha()
            && ColorSpace::Lab === $this->colorSpace()
        ) {
            return $this;
        }

        try {
            $vipsImage = $this->stripAlpha()->colourspace(ColorSpace::Lab->toVipsInterpretation());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to convert to Lab', $e);
        }
    }

    /**
     * Convert the image to HSV color space.
     *
     * Always returns a 3-band image: alpha (if present) is dropped.
     *
     * @return self New 3-band HSV Image instance
     *
     * @throws ImageException If the conversion fails
     */
    public function toHSV(): self
    {
        if (3 === $this->bands()
            && !$this->hasAlpha()
            && ColorSpace::HSV === $this->colorSpace()
        ) {
            return $this;
        }

        try {
            $vipsImage = $this->stripAlpha()->colourspace(ColorSpace::HSV->toVipsInterpretation());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to convert to HSV', $e);
        }
    }

    /**
     * Convert the image to CMYK color space.
     *
     * Always returns a 4-band image: alpha (if present) is dropped.
     *
     * @return self New 4-band CMYK Image instance
     *
     * @throws ImageException If the conversion fails
     */
    public function toCMYK(): self
    {
        if (4 === $this->bands()
            && !$this->hasAlpha()
            && ColorSpace::CMYK === $this->colorSpace()
        ) {
            return $this;
        }

        try {
            $vipsImage = $this->stripAlpha()->colourspace(ColorSpace::CMYK->toVipsInterpretation());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to convert to CMYK', $e);
        }
    }

    /**
     * Convert the image to Oklab color space.
     *
     * Always returns a 3-band float image: alpha (if present) is dropped.
     *
     * @return self New 3-band Oklab Image instance
     *
     * @throws ImageException If the conversion fails
     */
    public function toOklab(): self
    {
        if (3 === $this->bands()
            && !$this->hasAlpha()
            && ColorSpace::Oklab === $this->colorSpace()
        ) {
            return $this;
        }

        try {
            $vipsImage = $this->stripAlpha()->colourspace(ColorSpace::Oklab->toVipsInterpretation());

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to convert to Oklab', $e);
        }
    }

    /**
     * Ensure the image has an alpha (transparency) channel, adding it if necessary.
     *
     * Converts the image to sRGB if it isn't already, then adds a fully opaque
     * alpha band. Returns `$this` if the image is already a 4-band sRGB image.
     *
     * @return self New 4-band sRGB Image instance with alpha
     *
     * @throws ImageException If adding the alpha channel fails
     */
    public function toRGBA(): self
    {
        if (4 === $this->bands()
            && $this->hasAlpha()
            && ColorSpace::RGB === $this->colorSpace()
        ) {
            return $this;
        }

        try {
            $rgb = $this->stripAlpha()->colourspace(ColorSpace::RGB->toVipsInterpretation());

            $alpha = VipsImage::black($rgb->width, $rgb->height);
            $alpha = $alpha->linear(1, 255)->cast($rgb->format);
            $vipsImage = $rgb->bandjoin($alpha);
            $vipsImage = $vipsImage->copy(['interpretation' => ColorSpace::RGB->toVipsInterpretation()]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to add alpha channel', $e);
        }
    }

    /**
     * Flatten the alpha channel against a background color, removing transparency.
     *
     * @param null|Color $background Background color to flatten against (default: white)
     *
     * @return self New Image instance with alpha channel flattened
     *
     * @throws ImageException If flattening the alpha channel fails
     */
    public function flattenAlpha(?Color $background = null): self
    {
        if (!$this->hasAlpha()) {
            return $this;
        }

        try {
            $background ??= Color::white();
            // flatten() removes the alpha channel, so the background must match
            // the output band count (this->bands() - 1), not the source.
            $vipsImage = $this->vipsImage->flatten(['background' => $background->toArray($this->bands() - 1)]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to flatten alpha', $e);
        }
    }

    /**
     * Remove the alpha (transparency) channel from the image.
     *
     * @return self New Image instance with alpha channel removed
     *
     * @throws ImageException If removing the alpha channel fails
     */
    public function removeAlpha(): self
    {
        if (!$this->hasAlpha()) {
            return $this;
        }

        try {
            $vipsImage = $this->vipsImage->extract_band(0, ['n' => $this->bands() - 1]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to remove alpha', $e);
        }
    }

    /**
     * Premultiply the alpha channel with the color channels.
     *
     * This operation multiplies each color channel by the alpha value, which is
     * useful for certain compositing operations.
     *
     * @return self New Image instance with premultiplied alpha
     *
     * @throws ImageException If premultiplying alpha fails
     */
    public function premultiplyAlpha(): self
    {
        if (!$this->hasAlpha()) {
            return $this;
        }

        try {
            $vipsImage = $this->vipsImage->premultiply();

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to premultiply alpha', $e);
        }
    }

    /**
     * Unpremultiply the alpha channel from the color channels.
     *
     * This operation divides each color channel by the alpha value, reversing
     * the premultiplyAlpha operation.
     *
     * @return self New Image instance with unpremultiplied alpha
     *
     * @throws ImageException If unpremultiplying alpha fails
     */
    public function unpremultiplyAlpha(): self
    {
        if (!$this->hasAlpha()) {
            return $this;
        }

        try {
            $vipsImage = $this->vipsImage->unpremultiply();

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to unpremultiply alpha', $e);
        }
    }

    /**
     * Apply a mask image as the alpha channel of this image.
     *
     * The mask is converted to a single-band grayscale image where white pixels
     * become fully opaque and black pixels become fully transparent in the result.
     * If the source image already has an alpha channel it is replaced.
     *
     * Both images must have the same dimensions.
     *
     * @param self $mask Single-band grayscale mask image used as the alpha channel.
     *                   White (255) = fully opaque, black (0) = fully transparent.
     *
     * @return self new Image instance with the mask applied as its alpha channel
     *
     * @throws \InvalidArgumentException if the mask does not have the same dimensions as the image
     * @throws ImageException            if applying the mask fails
     */
    public function applyMask(self $mask): self
    {
        if ($this->width() !== $mask->width() || $this->height() !== $mask->height()) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'The mask dimensions (%dx%d) do not match the image dimensions (%dx%d).',
                    $mask->width(),
                    $mask->height(),
                    $this->width(),
                    $this->height(),
                )
            );
        }

        try {
            // Convert mask to single-band grayscale and extract as a raw band
            $maskBand = $mask->toGrayscale()->get(0);

            // Get the image's color bands without any existing alpha
            $bands = $this->split();
            if ($this->hasAlpha()) {
                array_pop($bands); // drop existing alpha
            }

            // Append mask as the new alpha channel and recombine
            $bands[] = $maskBand;

            return self::merge($bands);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to apply mask', $e);
        }
    }

    // -------------------------------------------------------------------------
    // Resize
    // -------------------------------------------------------------------------

    /**
     * Resize the image to the specified width and height.
     *
     * @param int    $width  Target width in pixels
     * @param int    $height Target height in pixels
     * @param Kernel $kernel Resampling kernel to use (default: Lanczos3)
     *
     * @return self New Image instance resized to the specified dimensions
     *
     * @throws ShapeException If width or height is less than 1
     * @throws ImageException If the resize operation fails
     */
    public function resize(int $width, int $height, Kernel $kernel = Kernel::Lanczos3): self
    {
        if ($width < 1 || $height < 1) {
            throw new ShapeException('resize dimensions must be positive');
        }

        try {
            $vipsImage = $this->vipsImage->resize(
                $width / $this->width(),
                ['vscale' => $height / $this->height(), 'kernel' => $kernel->value]
            );

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to resize image', $e);
        }
    }

    /**
     * Resize the image to the specified width while maintaining aspect ratio.
     *
     * @param int    $width  Target width in pixels
     * @param Kernel $kernel Resampling kernel to use (default: Lanczos3)
     *
     * @return self New Image instance resized to the specified width
     *
     * @throws ShapeException If width is less than 1
     */
    public function resizeToWidth(int $width, Kernel $kernel = Kernel::Lanczos3): self
    {
        if ($width < 1) {
            throw new ShapeException('width must be positive');
        }

        $scale = $width / $this->width();
        $height = (int) round($this->height() * $scale);

        return $this->resize($width, $height, $kernel);
    }

    /**
     * Resize the image to the specified height while maintaining aspect ratio.
     *
     * @param int    $height Target height in pixels
     * @param Kernel $kernel Resampling kernel to use (default: Lanczos3)
     *
     * @return self New Image instance resized to the specified height
     *
     * @throws ShapeException If height is less than 1
     */
    public function resizeToHeight(int $height, Kernel $kernel = Kernel::Lanczos3): self
    {
        if ($height < 1) {
            throw new ShapeException('height must be positive');
        }

        $scale = $height / $this->height();
        $width = (int) round($this->width() * $scale);

        return $this->resize($width, $height, $kernel);
    }

    /**
     * Scale the image by a uniform factor.
     *
     * @param float  $factor Scale factor (e.g. 0.5 to halve, 2.0 to double)
     * @param Kernel $kernel Resampling kernel to use (default: Lanczos3)
     *
     * @return self New Image instance scaled by the factor
     *
     * @throws ImageException If scaling fails
     */
    public function scale(float $factor, Kernel $kernel = Kernel::Lanczos3): self
    {
        try {
            $vipsImage = $this->vipsImage->resize($factor, ['kernel' => $kernel->value]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to scale image', $e);
        }
    }

    // -------------------------------------------------------------------------
    // Crop / Padding
    // -------------------------------------------------------------------------

    /**
     * Crop a rectangular region from the image.
     *
     * @param int $left   X-coordinate of the top-left corner of the crop region
     * @param int $top    Y-coordinate of the top-left corner of the crop region
     * @param int $width  Width of the crop region in pixels
     * @param int $height Height of the crop region in pixels
     *
     * @return self New Image instance containing the cropped region
     *
     * @throws ShapeException If crop parameters are invalid or region exceeds image bounds
     * @throws ImageException If the crop operation fails
     */
    public function crop(int $left, int $top, int $width, int $height): self
    {
        if ($left < 0 || $top < 0 || $width <= 0 || $height <= 0) {
            throw new ShapeException('Invalid crop parameters');
        }

        if ($left + $width > $this->width() || $top + $height > $this->height()) {
            throw new ShapeException('Crop region exceeds image bounds');
        }

        try {
            $vipsImage = $this->vipsImage->crop($left, $top, $width, $height);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to crop image', $e);
        }
    }

    /**
     * Crop a rectangle from the center of the image.
     *
     * If the requested dimensions are larger than the image, the image will be
     * scaled up proportionally before cropping.
     *
     * @param int $width  Width of the crop region in pixels
     * @param int $height Height of the crop region in pixels
     *
     * @return self New Image instance containing the center-cropped region
     *
     * @throws ShapeException If width or height is less than 1
     * @throws ImageException If the crop operation fails
     */
    public function centerCrop(int $width, int $height): self
    {
        if ($width < 1 || $height < 1) {
            throw new ShapeException('centerCrop dimensions must be positive');
        }

        $currentWidth = $this->width();
        $currentHeight = $this->height();

        if ($currentWidth < $width || $currentHeight < $height) {
            $scale = min($width / $currentWidth, $height / $currentHeight);

            return $this->scale($scale)->centerCrop($width, $height);
        }

        $left = (int) floor(($currentWidth - $width) / 2);
        $top = (int) floor(($currentHeight - $height) / 2);

        return $this->crop($left, $top, $width, $height);
    }

    /**
     * Crop the image using a BoundingBox.
     *
     * @param BoundingBox $box Bounding box defining the crop region
     *
     * @return self New Image instance containing the cropped region
     */
    public function cropBoundingBox(BoundingBox $box): self
    {
        $clampedBox = $box->clamp($this->width(), $this->height())->toInt();

        return $this->crop(
            (int) $clampedBox->x,
            (int) $clampedBox->y,
            (int) $clampedBox->width,
            (int) $clampedBox->height
        );
    }

    /**
     * Resize the image to fit within the specified dimensions, adding padding if necessary.
     *
     * The image is scaled down proportionally to fit within the width and height constraints,
     * then padded to exactly match the requested dimensions.
     *
     * @param int        $width    Target width in pixels
     * @param int        $height   Target height in pixels
     * @param null|Color $padColor Color to use for padding (default: null for black)
     *
     * @return self New Image instance with letterbox applied
     *
     * @throws ImageException If the letterbox operation fails
     */
    public function letterbox(int $width, int $height, ?Color $padColor = null): self
    {
        $scale = min($width / $this->width(), $height / $this->height());
        $resized = $this->scale($scale);

        $padWidth = (int) round(($width - $resized->width()) / 2);
        $padHeight = (int) round(($height - $resized->height()) / 2);

        return $resized->pad($padHeight, $padWidth, $padHeight, $padWidth, $padColor);
    }

    /**
     * Pad the image with extra pixels around the edges.
     *
     * @param int        $top        Number of pixels to add to the top
     * @param int        $right      Number of pixels to add to the right
     * @param int        $bottom     Number of pixels to add to the bottom
     * @param int        $left       Number of pixels to add to the left
     * @param null|Color $background Color to use for padding (default: black)
     *
     * @return self New Image instance with padding applied
     *
     * @throws ImageException If the padding operation fails
     */
    public function pad(int $top, int $right, int $bottom, int $left, ?Color $background = null): self
    {
        try {
            $background ??= Color::black();
            $width = $this->width() + $left + $right;
            $height = $this->height() + $top + $bottom;

            $vipsImage = $this->vipsImage->embed($left, $top, $width, $height, [
                'extend' => 'background',
                'background' => $background->toArray($this->bands()),
            ]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to pad image', $e);
        }
    }

    /**
     * Pad the image to exactly match the specified dimensions.
     *
     * The image will be positioned within the new dimensions according to the gravity setting,
     * and any extra space will be filled with the background color.
     *
     * @param int              $width      Target width in pixels
     * @param int              $height     Target height in pixels
     * @param null|Color       $background Color to use for padding (default: black)
     * @param CompassDirection $direction  Positioning of the original image within the padded area (default: CENTRE)
     *
     * @return self New Image instance padded to the specified dimensions
     *
     * @throws ImageException If the padding operation fails
     */
    public function padToSize(
        int $width,
        int $height,
        ?Color $background = null,
        CompassDirection $direction = CompassDirection::CENTRE,
    ): self {
        try {
            $background ??= Color::black();

            $vipsImage = $this->vipsImage->gravity($direction->value, $width, $height, [
                'extend' => 'background',
                'background' => $background->toArray($this->bands()),
            ]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw new ImageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    // -------------------------------------------------------------------------
    // Geometry
    // -------------------------------------------------------------------------

    /**
     * Flip the image along the specified axis.
     *
     * @param FlipDirection $direction Direction to flip (horizontal, vertical, or both)
     *
     * @return self New Image instance flipped along the specified axis
     *
     * @throws ImageException If the flip operation fails
     */
    public function flip(FlipDirection $direction): self
    {
        try {
            $vipsImage = match ($direction) {
                FlipDirection::Horizontal => $this->vipsImage->fliphor(),
                FlipDirection::Vertical => $this->vipsImage->flipver(),
                FlipDirection::Both => $this->vipsImage->fliphor()->flipver(),
            };

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to flip image', $e);
        }
    }

    /**
     * Rotate the image by the specified angle.
     *
     * @param float      $angle      Rotation angle in degrees (clockwise)
     * @param null|Color $background Background color to fill exposed areas after rotation (default: null)
     *
     * @return self New Image instance rotated by the specified angle
     *
     * @throws ImageException If the rotation operation fails
     */
    public function rotate(float $angle, ?Color $background = null): self
    {
        try {
            $options = [];
            if (null !== $background) {
                $options['background'] = $background->toArray($this->bands());
            }
            $vipsImage = $this->vipsImage->rotate($angle, $options);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to rotate image', $e);
        }
    }

    /**
     * Rotate the image 90 degrees clockwise.
     *
     * @return self New Image instance rotated 90 degrees clockwise
     *
     * @throws ImageException If the rotation operation fails
     */
    public function rot90(): self
    {
        try {
            return new self($this->vipsImage->rot(Angle::D90));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to rotate image', $e);
        }
    }

    /**
     * Rotate the image 180 degrees.
     *
     * @return self New Image instance rotated 180 degrees
     *
     * @throws ImageException If the rotation operation fails
     */
    public function rot180(): self
    {
        try {
            return new self($this->vipsImage->rot(Angle::D180));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to rotate image', $e);
        }
    }

    /**
     * Rotate the image 270 degrees clockwise (or 90 degrees counter-clockwise).
     *
     * @return self New Image instance rotated 270 degrees clockwise
     *
     * @throws ImageException If the rotation operation fails
     */
    public function rot270(): self
    {
        try {
            return new self($this->vipsImage->rot(Angle::D270));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to rotate image', $e);
        }
    }

    /**
     * Automatically rotate the image based on its EXIF orientation tag.
     *
     * @return self New Image instance automatically rotated according to EXIF data
     *
     * @throws ImageException If the auto-rotation operation fails
     */
    public function autoRotate(): self
    {
        try {
            return new self($this->vipsImage->autorot());
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to auto-rotate image', $e);
        }
    }

    // -------------------------------------------------------------------------
    // Pixel Value / Type Operations
    // -------------------------------------------------------------------------

    /**
     * Cast the image bands to a different data type.
     *
     * @param BandFormat $format Target band format to cast to
     *
     * @return self New Image instance with casted band format
     *
     * @throws ImageException If the cast operation fails
     */
    public function cast(BandFormat $format): self
    {
        try {
            return new self($this->vipsImage->cast($format->toString()));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to cast band format', $e);
        }
    }

    /**
     * Cast the image to unsigned 8-bit integer format.
     *
     * @return self New Image instance with UCHAR band format
     *
     * @throws ImageException If the cast operation fails
     */
    public function toUChar(): self
    {
        return $this->cast(BandFormat::UCHAR);
    }

    /**
     * Cast the image to 32-bit floating point format.
     *
     * @return self New Image instance with FLOAT band format
     *
     * @throws ImageException If the cast operation fails
     */
    public function toFloat(): self
    {
        return $this->cast(BandFormat::FLOAT);
    }

    /**
     * Cast the image to 64-bit floating point format.
     *
     * @return self New Image instance with DOUBLE band format
     *
     * @throws ImageException If the cast operation fails
     */
    public function toDouble(): self
    {
        return $this->cast(BandFormat::DOUBLE);
    }

    /**
     * Normalize the image pixel values using mean and standard deviation.
     *
     * Applies the transformation: (pixel - mean) / std for each channel.
     * If mean/std arrays have length 1, the same value is applied to all channels.
     *
     * @param float[] $mean Mean values for each channel (length = bands or 1)
     * @param float[] $std  Standard deviation values for each channel (length = bands or 1)
     *
     * @return self New Image instance with normalized pixel values
     *
     * @throws \InvalidArgumentException If mean/std arrays have invalid length
     * @throws ImageException            If normalization fails
     */
    public function normalize(array $mean, array $std): self
    {
        $bands = $this->bands();

        if (\count($mean) !== $bands && 1 !== \count($mean)) {
            throw new \InvalidArgumentException('Mean array must have length equal to bands or 1');
        }

        if (\count($std) !== $bands && 1 !== \count($std)) {
            throw new \InvalidArgumentException('Std array must have length equal to bands or 1');
        }

        try {
            $vipsImage = $this->vipsImage->linear(
                array_map(static fn ($s) => 1.0 / $s, $std),
                array_map(static fn ($m, $s) => -$m / $s, $mean, $std)
            );
            $vipsImage = $vipsImage->cast('float');

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to normalize image', $e);
        }
    }

    // -------------------------------------------------------------------------
    // Color / Pixel Adjustments
    // -------------------------------------------------------------------------

    /**
     * Adjust the brightness of the image.
     *
     * Multiplies each pixel value by the factor. Values are clamped to the valid range.
     * A factor of 1.0 preserves the original brightness.
     * A factor of 0.0 makes the image completely black.
     * A factor of 2.0 doubles the brightness.
     *
     * @param float $factor Brightness adjustment factor
     *
     * @return self New Image instance with adjusted brightness
     *
     * @throws ImageException If brightness adjustment fails
     */
    public function brightness(float $factor): self
    {
        try {
            $vipsImage = $this->vipsImage->linear($factor, 0);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to adjust brightness', $e);
        }
    }

    /**
     * Adjust the contrast of the image.
     *
     * Applies a linear transformation to increase or decrease contrast.
     * A factor of 1.0 preserves the original contrast.
     * A factor of 0.0 makes the image completely gray (midpoint).
     * A factor of 2.0 doubles the contrast.
     *
     * @param float $factor Contrast adjustment factor
     *
     * @return self New Image instance with adjusted contrast
     *
     * @throws ImageException If contrast adjustment fails
     */
    public function contrast(float $factor): self
    {
        try {
            $vipsImage = $this->vipsImage->linear($factor, 128 * (1 - $factor));

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to adjust contrast', $e);
        }
    }

    /**
     * Apply a per-band linear transformation: output = input × a + b.
     *
     * When `a` and `b` are scalars the same values are applied to every band.
     * When `a` and `b` are arrays each element corresponds to one band and
     * both arrays must have the same length (equal to the number of bands).
     *
     * Typical uses:
     *   - Brightness: `linear(1.2, 0)` — multiply every pixel by 1.2.
     *   - Contrast:   `linear(1.5, -64)` — scale then shift.
     *   - Per-band normalization: `linear([1 / s1, 1 / s2], [-m1 / s1, -m2 / s2])`.
     *
     * @param float|float[] $a Multiplier(s).  Scalar or array with one value per band.
     * @param float|float[] $b Offset(s).      Scalar or array with one value per band.
     *
     * @return self new Image instance with the linear transform applied
     *
     * @throws \InvalidArgumentException when both parameters are arrays with mismatched lengths
     * @throws ImageException            if the underlying VIPS operation fails
     */
    public function linear(array|float $a, array|float $b): self
    {
        if (\is_array($a) && \is_array($b) && \count($a) !== \count($b)) {
            throw new \InvalidArgumentException(
                \sprintf('Array parameters $a and $b must have the same length; %d vs %d', \count($a), \count($b))
            );
        }

        try {
            $vipsImage = $this->vipsImage->linear($a, $b);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to apply linear transformation', $e);
        }
    }

    /**
     * Adjust the saturation of the image.
     *
     * Converts to HSV color space, scales the saturation channel, then converts back.
     * A factor of 1.0 preserves the original saturation.
     * A factor of 0.0 removes all saturation (grayscale).
     * A factor of 2.0 doubles the saturation.
     *
     * @param float $factor Saturation adjustment factor
     *
     * @return self New Image instance with adjusted saturation
     *
     * @throws ImageException If saturation adjustment fails
     */
    public function saturation(float $factor): self
    {
        try {
            $vipsImage = $this->vipsImage->colourspace(Interpretation::HSV);
            $vipsImage = $vipsImage->linear([1, $factor, 1], [0, 0, 0]);
            $vipsImage = $vipsImage->colourspace(Interpretation::SRGB);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to adjust saturation', $e);
        }
    }

    /**
     * Adjust the hue of the image.
     *
     * Converts to HSV color space, shifts the hue channel, then converts back.
     * The hue shift is measured in degrees.
     *
     * @param float $degrees Hue shift in degrees (0-360, where 0 and 360 are equivalent)
     *
     * @return self New Image instance with adjusted hue
     *
     * @throws ImageException If hue adjustment fails
     */
    public function hue(float $degrees): self
    {
        try {
            $vipsImage = $this->vipsImage->colourspace(Interpretation::HSV);
            $vipsImage = $vipsImage->linear([1, 1, 1], [$degrees, 0, 0]);
            $vipsImage = $vipsImage->colourspace(Interpretation::SRGB);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to adjust hue', $e);
        }
    }

    /**
     * Apply gamma correction to the image.
     *
     * Adjusts the luminance of the image using a power-law transformation.
     * Values are normalized to [0,1] before applying the gamma factor.
     *
     * @param float $gamma Gamma correction factor
     *                     Values < 1.0 darken the image
     *                     Values > 1.0 brighten the image
     *                     Value of 1.0 preserves the original image
     *
     * @return self New Image instance with gamma correction applied
     *
     * @throws ImageException If gamma correction fails
     */
    public function gamma(float $gamma): self
    {
        try {
            return new self($this->vipsImage->gamma(['exponent' => $gamma]));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to apply gamma correction', $e);
        }
    }

    /**
     * Sharpen the image using an unsharp mask.
     *
     * Enhances edges and details in the image.
     *
     * @param null|float $sigma  The sigma parameter for the Gaussian blur (optional)
     * @param null|float $flat   The flat region threshold (m1 parameter) (optional)
     * @param null|float $jagged The edge threshold (m2 parameter) (optional)
     *
     * @return self New Image instance with sharpening applied
     *
     * @throws ImageException If sharpening fails
     */
    public function sharpen(?float $sigma = null, ?float $flat = null, ?float $jagged = null): self
    {
        try {
            $options = [];
            if (null !== $sigma) {
                $options['sigma'] = $sigma;
            }
            if (null !== $flat) {
                $options['m1'] = $flat;
            }
            if (null !== $jagged) {
                $options['m2'] = $jagged;
            }
            $vipsImage = $this->vipsImage->sharpen($options);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to sharpen image', $e);
        }
    }

    /**
     * Apply Gaussian blur to the image.
     *
     * Convolves the image with a Gaussian kernel of the specified sigma.
     * Larger sigma values create more blur.
     *
     * @param float $sigma Standard deviation of the Gaussian kernel
     *
     * @return self New Image instance with blur applied
     *
     * @throws ImageException If blur operation fails
     */
    public function blur(float $sigma): self
    {
        try {
            return new self($this->vipsImage->gaussblur($sigma));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to blur image', $e);
        }
    }

    /**
     * Apply median blur to the image.
     *
     * Replaces each pixel with the median value of its neighborhood.
     * Effective for removing salt-and-pepper noise while preserving edges.
     *
     * @param int $size The size of the square kernel (must be odd, default: 3)
     *
     * @return self New Image instance with median blur applied
     *
     * @throws ImageException If median blur fails
     */
    public function medianBlur(int $size = 3): self
    {
        try {
            return new self($this->vipsImage->median($size));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to apply median blur', $e);
        }
    }

    /**
     * Invert the colors of the image.
     *
     * Each pixel value is replaced with its inverse (max_value - pixel_value).
     * For 8-bit images, this is equivalent to 255 - pixel_value.
     *
     * @return self New Image instance with inverted colors
     *
     * @throws ImageException If inversion fails
     */
    public function invert(): self
    {
        try {
            return new self($this->vipsImage->invert());
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to invert image', $e);
        }
    }

    // -------------------------------------------------------------------------
    // Band Operations
    // -------------------------------------------------------------------------

    /**
     * Extract a single band (channel) from the image.
     *
     * @param int $index Zero-based index of the band to extract
     *
     * @return self New Image instance containing only the specified band
     *
     * @throws \InvalidArgumentException If the band index is out of range
     * @throws ImageException            If extracting the band fails
     */
    public function get(int $index): self
    {
        if ($index < 0 || $index >= $this->bands()) {
            throw new \InvalidArgumentException('Band index out of range');
        }

        try {
            return new self($this->vipsImage->extract_band($index));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to get band', $e);
        }
    }

    /**
     * Split the image into its individual bands (channels).
     *
     * @return self[] Array of Image instances, one for each band
     */
    public function split(): array
    {
        try {
            $bands = $this->vipsImage->bandsplit();

            return array_map(static fn ($band) => new self($band), $bands);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to split bands', $e);
        }
    }

    /**
     * Merge multiple single-band images into a multi-band image.
     *
     * @param static[] $bands Array of single-band Image instances to merge
     *
     * @return self New Image instance with merged bands
     *
     * @throws \InvalidArgumentException If bands array is empty or contains non-Image instances
     * @throws ImageException            If merging bands fails
     */
    public static function merge(array $bands): self
    {
        if ([] === $bands) {
            throw new \InvalidArgumentException('Bands array cannot be empty');
        }

        $bandImages = [];
        foreach ($bands as $band) {
            $bandImages[] = $band->vipsImage;
        }

        try {
            $vipsImage = VipsOperation::call('bandjoin', null, [$bandImages]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to merge bands', $e);
        }
    }

    /**
     * Reorder the bands (channels) of the image.
     *
     * @param int[] $order Array specifying the new order of bands (zero-based indices)
     *
     * @return self New Image instance with reordered bands
     *
     * @throws \InvalidArgumentException If any index in the order array is invalid
     * @throws ImageException            If reordering bands fails
     */
    public function reorder(array $order): self
    {
        try {
            /** @var VipsImage[] $bands */
            $bands = $this->vipsImage->bandsplit();

            $bandImages = [];
            foreach ($order as $i) {
                if (!isset($bands[$i])) {
                    throw new \InvalidArgumentException('Invalid band index');
                }
                $bandImages[] = $bands[$i];
            }

            $vipsImage = VipsOperation::call('bandjoin', null, [$bandImages]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to reorder bands', $e);
        }
    }

    // -------------------------------------------------------------------------
    // Math Operations
    // -------------------------------------------------------------------------

    /**
     * Multiply this image by another image, pixel-by-pixel.
     *
     * If the other image has fewer bands, it is automatically broadcast
     * across the bands of this image. Typical use: multiply an RGB image
     * by a single-band mask.
     *
     * @param self $other The image to multiply with
     *
     * @return self New Image instance
     *
     * @throws ImageException If the operation fails
     */
    public function multiply(self $other): self
    {
        try {
            return new self($this->vipsImage->multiply($other->vipsImage));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to multiply images', $e);
        }
    }

    /**
     * Add another image to this one, pixel-by-pixel.
     *
     * @param self $other The image to add
     *
     * @return self New Image instance
     *
     * @throws ImageException If the operation fails
     */
    public function add(self $other): self
    {
        try {
            return new self($this->vipsImage->add($other->vipsImage));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to add images', $e);
        }
    }

    /**
     * Subtract another image from this one, pixel-by-pixel.
     *
     * @param self $other The image to subtract
     *
     * @return self New Image instance
     *
     * @throws ImageException If the operation fails
     */
    public function subtract(self $other): self
    {
        try {
            return new self($this->vipsImage->subtract($other->vipsImage));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to subtract images', $e);
        }
    }

    /**
     * Divide this image by another image, pixel-by-pixel.
     *
     * @param self $other The image to divide by
     *
     * @return self New Image instance
     *
     * @throws ImageException If the operation fails
     */
    public function divide(self $other): self
    {
        try {
            return new self($this->vipsImage->divide($other->vipsImage));
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to divide images', $e);
        }
    }

    // -------------------------------------------------------------------------
    // Compositing / Drawing (Visualization Helpers)
    // -------------------------------------------------------------------------

    /**
     * Composite an overlay image onto this image.
     *
     * Places the overlay image at the specified position using the given blend mode.
     *
     * @param self   $overlay   The overlay image to composite
     * @param int    $x         X-coordinate for the overlay position
     * @param int    $y         Y-coordinate for the overlay position
     * @param string $blendMode Blend mode to use (default: 'over')
     *
     * @return self New Image instance with the overlay composited
     *
     * @throws ImageException If compositing fails
     */
    public function composite(self $overlay, int $x = 0, int $y = 0, string $blendMode = 'over'): self
    {
        try {
            $options = [
                'x' => $x,
                'y' => $y,
                'compositing_space' => $this->colorSpace()->toVipsInterpretation(),
            ];

            $vipsImage = $this->vipsImage->composite2($overlay->vipsImage, $blendMode, $options);
            $vipsImage = $vipsImage->copy(['interpretation' => $this->colorSpace()->toVipsInterpretation()]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to composite images', $e);
        }
    }

    /**
     * Draw a rectangle on the image.
     *
     * When `$blend` is false (the default) the color is written directly to
     * the destination pixels with no compositing. The result has the same
     * band count as the source image; any alpha in `$color` is dropped for
     * 3-band sources.
     *
     * When `$blend` is true, the color is alpha-composited onto the source
     * using the `over` blend mode. The result is always a 4-band sRGB image
     * so the alpha channel is preserved. This is slower than the non-blending
     * path.
     *
     * @param int   $left   X-coordinate of the top-left corner
     * @param int   $top    Y-coordinate of the top-left corner
     * @param int   $width  Width of the rectangle in pixels
     * @param int   $height Height of the rectangle in pixels
     * @param Color $color  Color of the rectangle
     * @param bool  $fill   Whether to fill the rectangle (default: false for outline only)
     * @param bool  $blend  Whether to alpha-composite the color onto the source
     *                      (default: false). When true the result is always 4-band.
     *
     * @return self New Image instance with the rectangle drawn
     *
     * @throws ImageException If drawing the rectangle fails
     */
    public function drawRect(int $left, int $top, int $width, int $height, Color $color, bool $fill = false, bool $blend = false): self
    {
        if (!$blend) {
            try {
                $options = [];
                if ($fill) {
                    $options['fill'] = true;
                }

                $vipsImage = $this->vipsImage->draw_rect($color->toFloatArray($this->bands()), $left, $top, $width, $height, $options);

                return new self($vipsImage);
            } catch (\Exception $e) {
                throw ImageException::wrap('Failed to draw rectangle', $e);
            }
        }

        return $this->drawBlended(static function (VipsImage $canvas) use ($left, $top, $width, $height, $color, $fill) {
            $options = [];
            if ($fill) {
                $options['fill'] = true;
            }

            return $canvas->draw_rect($color->toFloatArray(4), $left, $top, $width, $height, $options);
        }, 'Failed to draw rectangle');
    }

    /**
     * Draw a circle on the image.
     *
     * @param int   $cx     X-coordinate of the circle center
     * @param int   $cy     Y-coordinate of the circle center
     * @param int   $radius Radius of the circle in pixels
     * @param Color $color  Color of the circle
     * @param bool  $fill   Whether to fill the circle (default: false for outline only)
     * @param bool  $blend  Whether to alpha-composite the color onto the source
     *                      (default: false). When true the result is always 4-band.
     *
     * @return self New Image instance with the circle drawn
     *
     * @throws ImageException If drawing the circle fails
     */
    public function drawCircle(int $cx, int $cy, int $radius, Color $color, bool $fill = false, bool $blend = false): self
    {
        if (!$blend) {
            try {
                $options = [];
                if ($fill) {
                    $options['fill'] = true;
                }

                $vipsImage = $this->vipsImage->draw_circle($color->toFloatArray($this->bands()), $cx, $cy, $radius, $options);

                return new self($vipsImage);
            } catch (\Exception $e) {
                throw ImageException::wrap('Failed to draw circle', $e);
            }
        }

        return $this->drawBlended(static function (VipsImage $canvas) use ($cx, $cy, $radius, $color, $fill) {
            $options = [];
            if ($fill) {
                $options['fill'] = true;
            }

            return $canvas->draw_circle($color->toFloatArray(4), $cx, $cy, $radius, $options);
        }, 'Failed to draw circle');
    }

    /**
     * Draw a 1-pixel-wide line between two coordinates on the image.
     *
     * @param int   $x1    X-coordinate of the start point
     * @param int   $y1    Y-coordinate of the start point
     * @param int   $x2    X-coordinate of the end point
     * @param int   $y2    Y-coordinate of the end point
     * @param Color $color Color of the line
     * @param bool  $blend Whether to alpha-composite the color onto the source
     *                     (default: false). When true the result is always 4-band.
     *
     * @return self New Image instance with the line drawn
     *
     * @throws ImageException If drawing the line fails
     */
    public function drawLine(int $x1, int $y1, int $x2, int $y2, Color $color, bool $blend = false): self
    {
        if (!$blend) {
            try {
                $vipsImage = $this->vipsImage->draw_line($color->toFloatArray($this->bands()), $x1, $y1, $x2, $y2);

                return new self($vipsImage);
            } catch (\Exception $e) {
                throw ImageException::wrap('Failed to draw line', $e);
            }
        }

        return $this->drawBlended(static function (VipsImage $canvas) use ($x1, $y1, $x2, $y2, $color) {
            return $canvas->draw_line($color->toFloatArray(4), $x1, $y1, $x2, $y2);
        }, 'Failed to draw line');
    }

    /**
     * Draw ink onto the image using a single-band 8-bit image as a stencil mask.
     *
     * The mask is a monochrome image where each pixel value controls how much
     * of the ink color is applied to the source:
     *   -   0 (black)  → fully transparent — the original image is preserved
     * - 255 (white)  → fully opaque     — the ink color is applied at full strength
     *   -   1 … 254    → partial transparency proportional to the value
     *
     * @param self  $mask  Single-band 8-bit mask image used as a stencil
     * @param int   $x     X-coordinate where the mask is placed on the source
     * @param int   $y     Y-coordinate where the mask is placed on the source
     * @param Color $color The ink color to apply through the mask
     * @param bool  $blend Whether to alpha-composite the color onto the source
     *                     (default: false). When true the result is always 4-band.
     *
     * @return self New Image instance with the masked ink drawn
     *
     * @throws ImageException If drawing the mask fails
     */
    public function drawMask(self $mask, int $x, int $y, Color $color, bool $blend = false): self
    {
        if (!$blend) {
            try {
                $vipsImage = $this->vipsImage->draw_mask($color->toFloatArray($this->bands()), $mask->vipsImage, $x, $y);

                return new self($vipsImage);
            } catch (\Exception $e) {
                throw ImageException::wrap('Failed to draw mask', $e);
            }
        }

        return $this->drawBlended(static function (VipsImage $canvas) use ($mask, $x, $y, $color) {
            return $canvas->draw_mask($color->toFloatArray(4), $mask->vipsImage, $x, $y);
        }, 'Failed to draw mask');
    }

    /**
     * Draw text on the image.
     *
     * @param string           $text    Text to draw
     * @param int              $x       X-coordinate for the text position
     * @param int              $y       Y-coordinate for the text position
     * @param null|Color       $color   Color of the text (default: white)
     * @param null|TextOptions $options Text rendering options
     * @param bool             $blend   Whether to alpha-composite the color onto the source
     *                                  (default: false). When true the result is always 4-band.
     *
     * @return self New Image instance with the text drawn
     *
     * @throws ImageException If drawing the text fails
     */
    public function drawText(string $text, int $x, int $y, ?Color $color = null, ?TextOptions $options = null, bool $blend = false): self
    {
        $options ??= TextOptions::default();
        $color ??= Color::white();

        if (!$blend) {
            try {
                $textImage = VipsImage::text($text, $options->toVipsOptions());

                $vipsImage = $this->vipsImage->draw_mask($color->toFloatArray($this->bands()), $textImage, $x, $y);

                return new self($vipsImage);
            } catch (\Exception $e) {
                throw ImageException::wrap('Failed to draw text', $e);
            }
        }

        return $this->drawBlended(static function (VipsImage $canvas) use ($text, $options, $color, $x, $y) {
            $textImage = VipsImage::text($text, $options->toVipsOptions());

            return $canvas->draw_mask($color->toFloatArray(4), $textImage, $x, $y);
        }, 'Failed to draw text');
    }

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    /**
     * Export the image as an NDArray.
     *
     * Converts the image data to an NDArray with the specified channel format.
     * Symmetric with {@see fromArray()}.
     *
     * @param ChannelFormat $channelFormat Channel format of the output array (default: HWC)
     *
     * @return NDArray Image data as an NDArray
     *
     * @throws ImageException If exporting to NDArray fails
     */
    public function toArray(ChannelFormat $channelFormat = ChannelFormat::HWC): NDArray
    {
        try {
            $bufferString = $this->vipsImage->writeToMemory();
            $size = \strlen($bufferString);

            $width = $this->width();
            $height = $this->height();
            $bands = $this->bands();
            $dtype = $this->bandFormat()->toDtype();
            $shape = [$height, $width, $bands];

            $ffi = \FFI::cdef();
            $buffer = $ffi->new("uint8_t[{$size}]");
            \assert($buffer instanceof CData);
            \FFI::memcpy($buffer, $bufferString, $size);

            $nd = NDArray::fromBuffer($buffer, $shape, $dtype);

            return ChannelFormat::CHW === $channelFormat ? $nd->permute(2, 0, 1) : $nd;
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to export to NDArray', $e);
        }
    }

    /**
     * Export the image as a raw FFI memory buffer.
     *
     * Complement of {@see fromMemory()}. Returns the raw, uncompressed pixel
     * data as an FFI CData buffer. The caller is responsible for ensuring
     * the buffer is freed or goes out of scope appropriately.
     *
     * @return CData Raw pixel buffer (FFI CData pointer)
     *
     * @throws ImageException If exporting to memory fails
     */
    public function toMemory(): CData
    {
        try {
            $ffi = \FFI::cdef();

            $bufferString = $this->vipsImage->writeToMemory();
            $size = \strlen($bufferString);
            $buffer = $ffi->new("uint8_t[{$size}]");
            \assert($buffer instanceof CData);
            \FFI::memcpy($buffer, $bufferString, $size);

            return $buffer;
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to export to memory', $e);
        }
    }

    /**
     * Encode the image and write it to a file.
     *
     * The output format is inferred from the file extension.
     * Complement of {@see fromFile()}.
     *
     * @param string           $path    Destination file path
     * @param null|SaveOptions $options Format-specific encoding options
     *
     * @throws ImageException If writing the file fails
     */
    public function toFile(string $path, ?SaveOptions $options = null): void
    {
        $vipsOptions = $options?->toVipsOptions() ?? [];

        try {
            $this->vipsImage->writeToFile($path, $vipsOptions);
        } catch (\Exception $e) {
            throw ImageException::wrap("Failed to write image to {$path}", $e);
        }
    }

    /**
     * Encode the image to a binary string in the specified format.
     *
     * Complement of {@see fromBuffer()}.
     *
     * @param ImageFormat      $format  Target image format
     * @param null|SaveOptions $options Format-specific encoding options
     *
     * @return string Encoded image data
     *
     * @throws ImageException If encoding fails
     */
    public function toBuffer(ImageFormat $format, ?SaveOptions $options = null): string
    {
        $vipsOptions = $options?->toVipsOptions() ?? [];

        try {
            return $this->vipsImage->writeToBuffer($format->suffix(), $vipsOptions);
        } catch (\Exception $e) {
            throw ImageException::wrap("Failed to encode image to {$format->value}", $e);
        }
    }

    // -------------------------------------------------------------------------
    // Misc
    // -------------------------------------------------------------------------

    /**
     * Create a copy of the image.
     *
     * @return self New Image instance that is a copy of this image
     *
     * @throws ImageException If copying the image fails
     */
    public function copy(): self
    {
        try {
            $vipsImage = $this->vipsImage->copy();

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap('Failed to copy image', $e);
        }
    }

    /**
     * Apply a draw operation with alpha blending. Draws the shape onto a
     * transparent 4-band sRGB canvas, then composites the canvas over the
     * (RGBA-converted) source using the `over` blend mode. The result is
     * always a 4-band sRGB image.
     */
    private function drawBlended(callable $drawOnCanvas, string $errorMessage): self
    {
        try {
            $baseRgba = $this->toRGBA()->vipsImage;

            $width = $baseRgba->width;
            $height = $baseRgba->height;

            $canvas = VipsImage::black($width, $height)
                ->bandjoin([VipsImage::black($width, $height), VipsImage::black($width, $height), VipsImage::black($width, $height)])
                ->copy(['interpretation' => ColorSpace::RGB->toVipsInterpretation()])
                ->cast('uchar');

            $stamp = $drawOnCanvas($canvas);

            $vipsImage = $baseRgba->composite2($stamp, 'over', [
                'compositing_space' => ColorSpace::RGB->toVipsInterpretation(),
            ]);
            $vipsImage = $vipsImage->copy(['interpretation' => ColorSpace::RGB->toVipsInterpretation()]);

            return new self($vipsImage);
        } catch (\Exception $e) {
            throw ImageException::wrap($errorMessage, $e);
        }
    }

    /**
     * Remove the alpha band from the image if present.
     */
    private function stripAlpha(): VipsImage
    {
        if (!$this->hasAlpha()) {
            return $this->vipsImage;
        }

        return $this->vipsImage->extract_band(0, ['n' => $this->bands() - 1]);
    }
}
