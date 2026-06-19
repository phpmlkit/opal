# Image Class

High-performance image backed by libvips via FFI.

## Overview

`Image` is the core class of this library. It wraps a VIPS image handle and provides an expressive, type-safe API for
loading, transforming, drawing, and exporting images.

All transformation methods are **immutable** and return a new `Image`. Computation is **lazy** — VIPS pipelines are only
executed when the image data is actually consumed (`toArray()`, `toBuffer()`, `toFile()`, `toMemory()`).

```php
final readonly class Image
```

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\Kernel;

$img = Image::fromFile('photo.jpg');
$processed = $img
    ->resize(1920, 1080, Kernel::Lanczos3)
    ->sharpen(sigma: 1.5)
    ->saturation(1.1);
$processed->toFile('output.jpg');
```

---

## Factory / Loading

### fromFile()

Load an image from a file path. Format is detected automatically from the file header.

```php
public static function fromFile(string $path, ?LoadOptions $options = null): self
```

**Parameters:**

| Parameter  | Type           | Description              |
|------------|----------------|--------------------------|
| `$path`    | `string`       | Path to the image file   |
| `$options` | `?LoadOptions` | Optional loading options |

**Returns:** New Image instance

**Throws:** `FileNotFoundException` if the file does not exist, `InvalidImageException` if the file cannot be loaded.

**Examples:**

```php
// Simple load
$img = Image::fromFile('photo.jpg');

// Load with options
$img = Image::fromFile('multi-page.tif', (new LoadOptions())->withPage(1));
```

---

### fromBuffer()

Decode an image from a binary string (e.g. from `file_get_contents()` or HTTP response).

```php
public static function fromBuffer(string $buffer, ?LoadOptions $options = null): self
```

**Parameters:**

| Parameter  | Type           | Description              |
|------------|----------------|--------------------------|
| `$buffer`  | `string`       | Binary image data        |
| `$options` | `?LoadOptions` | Optional loading options |

**Returns:** New Image instance

**Throws:** `InvalidImageException` if the buffer cannot be loaded.

**Examples:**

```php
// From file_get_contents
$data = file_get_contents('photo.jpg');
$img = Image::fromBuffer($data);

// From HTTP response
$response = file_get_contents('https://example.com/image.png');
$img = Image::fromBuffer($response);
```

---

### fromMemory()

Wrap a raw pixel buffer already in memory. This method creates a copy of the buffer data.

```php
public static function fromMemory(
    CData $buffer,
    int $width,
    int $height,
    int $bands,
    ColorSpace $colorSpace,
    BandFormat $bandFormat = BandFormat::UCHAR,
): self
```

**Parameters:**

| Parameter     | Type         | Default | Description                                        |
|---------------|--------------|---------|----------------------------------------------------|
| `$buffer`     | `CData`      | —       | Raw pixel buffer (FFI CData)                       |
| `$width`      | `int`        | —       | Image width in pixels                              |
| `$height`     | `int`        | —       | Image height in pixels                             |
| `$bands`      | `int`        | —       | Number of color bands (e.g. 3 for RGB, 4 for RGBA) |
| `$colorSpace` | `ColorSpace` | —       | Color space of the buffer data                     |
| `$bandFormat` | `BandFormat` | `UCHAR` | Data type of each band                             |

**Returns:** New Image instance

**Throws:** `InvalidImageException` if the image cannot be created.

---

### fromArray()

Create an Image from a PHP array or NDArray.

Expected layout:

- `ChannelFormat::HWC` — `[H, W, C]` (TensorFlow / NumPy default)
- `ChannelFormat::CHW` — `[C, H, W]` (PyTorch default)

For NDArray, the buffer is copied into a new Image.

```php
public static function fromArray(
    array|NDArray $array,
    ColorSpace $colorSpace = ColorSpace::RGB,
    ChannelFormat $channelFormat = ChannelFormat::HWC,
): self
```

**Parameters:**

| Parameter        | Type             | Default | Description                          |
|------------------|------------------|---------|--------------------------------------|
| `$array`         | `array\|NDArray` | —       | Input pixel data as array or NDArray |
| `$colorSpace`    | `ColorSpace`     | `RGB`   | Color space of the image data        |
| `$channelFormat` | `ChannelFormat`  | `HWC`   | Format of the array data             |

**Returns:** New Image instance

**Throws:** `ShapeException` if the array does not have exactly 3 dimensions, or channel dimension is not 1–4.
`IndexException` from NDArray operations.

**Examples:**

```php
use PhpMlKit\Opal\ChannelFormat;

// From PHP array (HWC layout)
$pixels = [[[255, 0, 0], [0, 255, 0]], [[0, 0, 255], [255, 255, 0]]];
$img = Image::fromArray($pixels);

// From NDArray in CHW layout (PyTorch style)
$ndarray = NDArray::zeros([3, 224, 224]);
$img = Image::fromArray($ndarray, ColorSpace::RGB, ChannelFormat::CHW);
```

---

### blank()

Create a blank image filled with a background color (or black/transparent).

```php
public static function blank(
    int $width,
    int $height,
    ColorSpace $colorSpace = ColorSpace::RGB,
    ?Color $background = null,
): self
```

**Parameters:**

| Parameter     | Type         | Default | Description                                 |
|---------------|--------------|---------|---------------------------------------------|
| `$width`      | `int`        | —       | Width of the blank image in pixels          |
| `$height`     | `int`        | —       | Height of the blank image in pixels         |
| `$colorSpace` | `ColorSpace` | `RGB`   | Color space of the image                    |
| `$background` | `?Color`     | `null`  | Background color (null = black/transparent) |

**Returns:** New blank Image instance

**Throws:** `InvalidImageException` if the blank image cannot be created.

**Examples:**

```php
// Black 100x100 RGB image
$img = Image::blank(100, 100);

// Red 640x480 image
$img = Image::blank(640, 480, ColorSpace::RGB, Color::red());

// Transparent 512x512 RGBA image
$img = Image::blank(512, 512, ColorSpace::RGB, Color::transparent());
```

---

### text()

Create a text image.

```php
public static function text(string $text, ?TextOptions $options = null): self
```

**Parameters:**

| Parameter  | Type           | Description                     |
|------------|----------------|---------------------------------|
| `$text`    | `string`       | The text to render              |
| `$options` | `?TextOptions` | Optional text rendering options |

**Returns:** New image containing the text

**Throws:** `ImageException` if text image creation fails.

**Examples:**

```php
// Default rendering
$img = Image::text('Hello World');

// Custom font and size
$img = Image::text('Hello World', TextOptions::default()
    ->withFont('sans-serif')
    ->withFontSize(48)
);
```

---

### thumbnail()

Create a thumbnail from a file using shrink-on-load. This is faster and uses less memory than loading the full image
then resizing — it exploits the shrink-at-decode features built into formats such as JPEG, WebP and TIFF so that only
the data needed for the target size is decoded.

```php
public static function thumbnail(
    string $filename,
    int $width,
    ?int $height = null,
    bool $noRotate = false,
    bool $linear = false,
): self
```

**Parameters:**

| Parameter   | Type     | Default | Description                                                                         |
|-------------|----------|---------|-------------------------------------------------------------------------------------|
| `$filename` | `string` | —       | Path to the image file                                                              |
| `$width`    | `int`    | —       | Target width in pixels                                                              |
| `$height`   | `?int`   | `null`  | Optional target height. When omitted the aspect ratio is determined by width alone. |
| `$noRotate` | `bool`   | `false` | Whether to skip auto-rotation based on EXIF orientation                             |
| `$linear`   | `bool`   | `false` | Whether to perform the shrink in linear light                                       |

**Returns:** New Image instance containing the thumbnail

**Throws:** `FileNotFoundException` if the file does not exist, `InvalidImageException` if thumbnailing fails.

**Examples:**

```php
// Thumbnail with width 300, auto height
$thumb = Image::thumbnail('photo.jpg', 300);

// Exact dimensions
$thumb = Image::thumbnail('photo.jpg', 300, 200);

// Skip auto-rotation
$thumb = Image::thumbnail('photo.jpg', 300, noRotate: true);
```

---

## Metadata / Inspection

### width()

Get the width of the image in pixels.

```php
public function width(): int
```

**Returns:** Image width in pixels.

### height()

Get the height of the image in pixels.

```php
public function height(): int
```

**Returns:** Image height in pixels.

### bands()

Get the number of color bands in the image.

```php
public function bands(): int
```

**Returns:** Number of bands (e.g. 1 for grayscale, 3 for RGB, 4 for RGBA).

### size()

Get the size of the image as an ImageSize object.

```php
public function size(): ImageSize
```

**Returns:** `ImageSize` object containing width and height.

### colorSpace()

Get the color space of the image.

```php
public function colorSpace(): ColorSpace
```

**Returns:** The `ColorSpace` of the image.

### bandFormat()

Get the band format (data type) of the image.

```php
public function bandFormat(): BandFormat
```

**Returns:** The `BandFormat` used for each band.

### hasAlpha()

Check if the image has an alpha (transparency) channel.

```php
public function hasAlpha(): bool
```

**Returns:** `true` if the image has an alpha channel, `false` otherwise.

### pageCount()

Get the number of pages in the image (for multipage formats like TIFF).

```php
public function pageCount(): int
```

**Returns:** Number of pages (default 1 for single-page images).

### resolution()

Get the resolution of the image in DPI (dots per inch).

```php
public function resolution(): array
```

**Returns:** Associative array with `'x'` and `'y'` keys representing horizontal and vertical DPI.

### exif()

Get EXIF metadata from the image.

```php
public function exif(): array
```

**Returns:** Associative array of EXIF data (empty array if no EXIF data present).

### iccProfile()

Get the ICC profile data from the image.

```php
public function iccProfile(): ?string
```

**Returns:** The ICC profile data as a string, or `null` if no profile is present.

**Example:**

```php
$img = Image::fromFile('photo.jpg');

echo $img->width();               // e.g. 4032
echo $img->height();              // e.g. 3024
echo $img->bands();               // e.g. 3
echo $img->hasAlpha();            // false
echo $img->bandFormat()->name;    // UCHAR
echo $img->colorSpace()->name;    // RGB

$size = $img->size();
echo $size;                       // "4032x3024"

$res = $img->resolution();
echo $res['x'];                   // e.g. 72

$exif = $img->exif();
// ... inspect EXIF data
```

---

## Color Space Conversion

Color space conversion is performed by typed methods, one per supported target space. Each
method drops any existing alpha channel (since libvips interprets alpha as a band, not as
part of the color space), performs the pixel conversion, and returns a new image in the
target space.

### toRGB()

Convert the image to sRGB color space. Drops any alpha channel and returns a 3-band image.

```php
public function toRGB(): self
```

**Returns:** New 3-band sRGB Image instance.

**Throws:** `ImageException` if the conversion fails.

### toGrayscale()

Convert the image to grayscale (1-band). Drops any alpha channel.

```php
public function toGrayscale(): self
```

**Returns:** New 1-band grayscale Image instance.

**Throws:** `ImageException` if the conversion fails.

### toLab()

Convert the image to CIE Lab color space. Drops any alpha channel.

```php
public function toLab(): self
```

**Returns:** New 3-band Lab Image instance (float band format).

**Throws:** `ImageException` if the conversion fails.

### toHSV()

Convert the image to HSV color space. Drops any alpha channel.

```php
public function toHSV(): self
```

**Returns:** New 3-band HSV Image instance.

**Throws:** `ImageException` if the conversion fails.

### toCMYK()

Convert the image to CMYK color space. Drops any alpha channel.

```php
public function toCMYK(): self
```

**Returns:** New 4-band CMYK Image instance.

**Throws:** `ImageException` if the conversion fails.

### toOklab()

Convert the image to Oklab color space. Drops any alpha channel.

```php
public function toOklab(): self
```

**Returns:** New 3-band Oklab Image instance (float band format).

**Throws:** `ImageException` if the conversion fails.

### toRGBA()

Ensure the image has an alpha (transparency) channel, adding it if necessary.

```php
public function toRGBA(): self
```

**Returns:** New Image instance with alpha channel. Returns `$this` if alpha already exists.

**Throws:** `ImageException` if adding the alpha channel fails.

### flattenAlpha()

Flatten the alpha channel against a background color, removing transparency.

```php
public function flattenAlpha(?Color $background = null): self
```

**Parameters:**

| Parameter     | Type     | Default | Description                       |
|---------------|----------|---------|-----------------------------------|
| `$background` | `?Color` | `null`  | Background color (default: white) |

**Returns:** New Image instance with alpha flattened. Returns `$this` if no alpha channel exists.

**Throws:** `ImageException` if flattening fails.

### removeAlpha()

Remove the alpha (transparency) channel from the image.

```php
public function removeAlpha(): self
```

**Returns:** New Image instance with alpha removed. Returns `$this` if no alpha channel exists.

**Throws:** `ImageException` if removal fails.

### premultiplyAlpha()

Premultiply the alpha channel with the color channels. This multiplies each color channel by the alpha value, useful for
certain compositing operations.

```php
public function premultiplyAlpha(): self
```

**Returns:** New Image instance with premultiplied alpha. Returns `$this` if no alpha channel exists.

**Throws:** `ImageException` if premultiplication fails.

### unpremultiplyAlpha()

Unpremultiply the alpha channel from the color channels. Reverses `premultiplyAlpha()`.

```php
public function unpremultiplyAlpha(): self
```

**Returns:** New Image instance with unpremultiplied alpha. Returns `$this` if no alpha channel exists.

**Throws:** `ImageException` if unpremultiplication fails.

### applyMask()

Apply a mask image as the alpha channel of this image.

The mask is converted to a single-band grayscale image where white pixels become fully opaque and black pixels become
fully transparent in the result. If the source image already has an alpha channel it is replaced.

Both images must have the same dimensions.

```php
public function applyMask(self $mask): self
```

**Parameters:**

| Parameter | Type   | Description                                                                                                           |
|-----------|--------|-----------------------------------------------------------------------------------------------------------------------|
| `$mask`   | `self` | Single-band grayscale mask image used as the alpha channel. White (255) = fully opaque, black (0) = fully transparent. |

**Returns:** New Image instance with the mask applied as its alpha channel

**Throws:** `\InvalidArgumentException` if the mask does not have the same dimensions as the image, `ImageException` if
applying the mask fails.

**Examples:**

```php
// Convert to grayscale
$gray = $img->toGrayscale();

// Add alpha channel
$rgba = $img->toRGBA();

// Convert between color spaces (alpha is always dropped)
$lab = $img->toLab();
$hsv = $img->toHSV();
$cmyk = $img->toCMYK();
$oklab = $img->toOklab();

// Flatten transparency against white background
$opaque = $img->flattenAlpha(Color::white());

// Round-trip premultiplication
$premul = $img->premultiplyAlpha();
// ... compositing operations ...
$unpremul = $premul->unpremultiplyAlpha();

// Apply a grayscale mask as the alpha channel
$mask = Image::fromFile('mask.png')->toGrayscale();
$masked = $img->applyMask($mask);
```

---

## Resize

### resize()

Resize the image to the specified width and height.

```php
public function resize(int $width, int $height, Kernel $kernel = Kernel::Lanczos3): self
```

**Parameters:**

| Parameter | Type     | Default    | Description             |
|-----------|----------|------------|-------------------------|
| `$width`  | `int`    | —          | Target width in pixels  |
| `$height` | `int`    | —          | Target height in pixels |
| `$kernel` | `Kernel` | `Lanczos3` | Resampling kernel       |

**Returns:** New Image instance resized to the specified dimensions

**Throws:** `ShapeException` if width or height is less than 1, `ImageException` if resize fails.

### resizeToWidth()

Resize the image to the specified width while maintaining aspect ratio.

```php
public function resizeToWidth(int $width, Kernel $kernel = Kernel::Lanczos3): self
```

**Parameters:**

| Parameter | Type     | Default    | Description            |
|-----------|----------|------------|------------------------|
| `$width`  | `int`    | —          | Target width in pixels |
| `$kernel` | `Kernel` | `Lanczos3` | Resampling kernel      |

**Returns:** New Image instance resized to the specified width

**Throws:** `ShapeException` if width is less than 1.

### resizeToHeight()

Resize the image to the specified height while maintaining aspect ratio.

```php
public function resizeToHeight(int $height, Kernel $kernel = Kernel::Lanczos3): self
```

**Parameters:**

| Parameter | Type     | Default    | Description             |
|-----------|----------|------------|-------------------------|
| `$height` | `int`    | —          | Target height in pixels |
| `$kernel` | `Kernel` | `Lanczos3` | Resampling kernel       |

**Returns:** New Image instance resized to the specified height

**Throws:** `ShapeException` if height is less than 1.

### scale()

Scale the image by a uniform factor.

```php
public function scale(float $factor, Kernel $kernel = Kernel::Lanczos3): self
```

**Parameters:**

| Parameter | Type     | Default    | Description                                     |
|-----------|----------|------------|-------------------------------------------------|
| `$factor` | `float`  | —          | Scale factor (e.g. 0.5 to halve, 2.0 to double) |
| `$kernel` | `Kernel` | `Lanczos3` | Resampling kernel                               |

**Returns:** New Image instance scaled by the factor

**Throws:** `ImageException` if scaling fails.

**Examples:**

```php
use PhpMlKit\Opal\Kernel;

// Exact dimensions
$resized = $img->resize(800, 600);

// Maintain aspect ratio by width
$resized = $img->resizeToWidth(800);

// Uniform scale
$half = $img->scale(0.5);
$double = $img->scale(2.0);

// Fast, lower quality (good for thumbnails)
$fast = $img->resizeToWidth(300, Kernel::Nearest);
```

---

## Crop / Padding

### crop()

Crop a rectangular region from the image.

```php
public function crop(int $left, int $top, int $width, int $height): self
```

**Parameters:**

| Parameter | Type  | Description                         |
|-----------|-------|-------------------------------------|
| `$left`   | `int` | X-coordinate of the top-left corner |
| `$top`    | `int` | Y-coordinate of the top-left corner |
| `$width`  | `int` | Width of the crop region            |
| `$height` | `int` | Height of the crop region           |

**Returns:** New Image instance containing the cropped region

**Throws:** `ShapeException` if crop parameters are invalid or region exceeds image bounds, `ImageException` if the crop
operation fails.

### centerCrop()

Crop a rectangle from the center of the image. If the requested dimensions are larger than the image, the image will be
scaled up proportionally before cropping.

```php
public function centerCrop(int $width, int $height): self
```

**Parameters:**

| Parameter | Type  | Description               |
|-----------|-------|---------------------------|
| `$width`  | `int` | Width of the crop region  |
| `$height` | `int` | Height of the crop region |

**Returns:** New Image instance containing the center-cropped region

**Throws:** `ShapeException` if width or height is less than 1, `ImageException` if the crop operation fails.

### cropBoundingBox()

Crop the image using a BoundingBox. The box is automatically clamped to image bounds.

```php
public function cropBoundingBox(BoundingBox $box): self
```

**Parameters:**

| Parameter | Type          | Description                           |
|-----------|---------------|---------------------------------------|
| `$box`    | `BoundingBox` | Bounding box defining the crop region |

**Returns:** New Image instance containing the cropped region

### letterbox()

Resize the image to fit within the specified dimensions, adding padding if necessary. The image is scaled down
proportionally to fit within the width and height constraints, then padded to exactly match the requested dimensions.

```php
public function letterbox(int $width, int $height, ?Color $padColor = null): self
```

**Parameters:**

| Parameter   | Type     | Default | Description                             |
|-------------|----------|---------|-----------------------------------------|
| `$width`    | `int`    | —       | Target width in pixels                  |
| `$height`   | `int`    | —       | Target height in pixels                 |
| `$padColor` | `?Color` | `null`  | Color to use for padding (null = black) |

**Returns:** New Image instance with letterbox applied

**Throws:** `ImageException` if the operation fails.

### pad()

Pad the image with extra pixels around the edges.

```php
public function pad(int $top, int $right, int $bottom, int $left, ?Color $background = null): self
```

**Parameters:**

| Parameter     | Type     | Default | Description                           |
|---------------|----------|---------|---------------------------------------|
| `$top`        | `int`    | —       | Number of pixels to add to the top    |
| `$right`      | `int`    | —       | Number of pixels to add to the right  |
| `$bottom`     | `int`    | —       | Number of pixels to add to the bottom |
| `$left`       | `int`    | —       | Number of pixels to add to the left   |
| `$background` | `?Color` | `null`  | Color for padding (null = black)      |

**Returns:** New Image instance with padding applied

**Throws:** `ImageException` if padding fails.

### padToSize()

Pad the image to exactly match the specified dimensions. The image will be positioned within the new dimensions
according to the gravity setting, and any extra space will be filled with the background color.

```php
public function padToSize(
    int $width,
    int $height,
    ?Color $background = null,
    CompassDirection $direction = CompassDirection::CENTRE,
): self
```

**Parameters:**

| Parameter     | Type               | Default  | Description                                              |
|---------------|--------------------|----------|----------------------------------------------------------|
| `$width`      | `int`              | —        | Target width in pixels                                   |
| `$height`     | `int`              | —        | Target height in pixels                                  |
| `$background` | `?Color`           | `null`   | Color for padding (null = black)                         |
| `$direction`  | `CompassDirection` | `CENTRE` | Positioning of the original image within the padded area |

**Returns:** New Image instance padded to the specified dimensions

**Throws:** `ImageException` if padding fails.

**Examples:**

```php
// Crop a specific region
$cropped = $img->crop(100, 50, 400, 300);

// Center crop to square
$square = $img->centerCrop(500, 500);

// Crop using bounding box
$box = BoundingBox::fromCorners(50, 50, 200, 200);
$cropped = $img->cropBoundingBox($box);

// Letterbox to fit 1920x1080
$letterboxed = $img->letterbox(1920, 1080, Color::black());

// Add 20px padding on all sides
$padded = $img->pad(20, 20, 20, 20, Color::white());

// Pad to exact size, position at top-left
$padded = $img->padToSize(1920, 1080, Color::black(), CompassDirection::NORTH_WEST);
```

---

## Geometry

### flip()

Flip the image along the specified axis.

```php
public function flip(FlipDirection $direction): self
```

**Parameters:**

| Parameter    | Type            | Description                                       |
|--------------|-----------------|---------------------------------------------------|
| `$direction` | `FlipDirection` | Direction to flip (Horizontal, Vertical, or Both) |

**Returns:** New Image instance flipped along the specified axis

**Throws:** `ImageException` if the flip operation fails.

### rotate()

Rotate the image by the specified angle.

```php
public function rotate(float $angle, ?Color $background = null): self
```

**Parameters:**

| Parameter     | Type     | Default | Description                           |
|---------------|----------|---------|---------------------------------------|
| `$angle`      | `float`  | —       | Rotation angle in degrees (clockwise) |
| `$background` | `?Color` | `null`  | Background color for exposed areas    |

**Returns:** New Image instance rotated by the specified angle

**Throws:** `ImageException` if rotation fails.

### rot90()

Rotate the image 90 degrees clockwise.

```php
public function rot90(): self
```

**Returns:** New Image instance rotated 90 degrees clockwise

**Throws:** `ImageException` if rotation fails.

### rot180()

Rotate the image 180 degrees.

```php
public function rot180(): self
```

**Returns:** New Image instance rotated 180 degrees

**Throws:** `ImageException` if rotation fails.

### rot270()

Rotate the image 270 degrees clockwise (or 90 degrees counter-clockwise).

```php
public function rot270(): self
```

**Returns:** New Image instance rotated 270 degrees clockwise

**Throws:** `ImageException` if rotation fails.

### autoRotate()

Automatically rotate the image based on its EXIF orientation tag.

```php
public function autoRotate(): self
```

**Returns:** New Image instance automatically rotated according to EXIF data

**Throws:** `ImageException` if auto-rotation fails.

**Examples:**

```php
use PhpMlKit\Opal\FlipDirection;

// Flip horizontally (mirror)
$flipped = $img->flip(FlipDirection::Horizontal);

// Rotate arbitrary angle with background fill
$rotated = $img->rotate(45, Color::white());

// Quick 90-degree rotations
$img->rot90();
$img->rot180();
$img->rot270();

// Auto-rotate based on EXIF
$corrected = $img->autoRotate();
```

---

## Pixel Value / Type Operations

### cast()

Cast the image bands to a different data type.

```php
public function cast(BandFormat $format): self
```

**Parameters:**

| Parameter | Type         | Description                   |
|-----------|--------------|-------------------------------|
| `$format` | `BandFormat` | Target band format to cast to |

**Returns:** New Image instance with casted band format

**Throws:** `ImageException` if the cast operation fails.

### toUChar()

Cast the image to unsigned 8-bit integer format.

```php
public function toUChar(): self
```

**Returns:** New Image instance with UCHAR band format.

### toFloat()

Cast the image to 32-bit floating point format.

```php
public function toFloat(): self
```

**Returns:** New Image instance with FLOAT band format.

### toDouble()

Cast the image to 64-bit floating point format.

```php
public function toDouble(): self
```

**Returns:** New Image instance with DOUBLE band format.

### rescalePixels()

*Note: Not available — use `linear()` or `normalize()` for pixel rescaling.*

**Examples:**

```php
// Cast to float for computation
$float = $img->toFloat();

// Cast back to 8-bit for saving
$uchar = $float->toUChar();
```

---

## Color Adjustments

### brightness()

Adjust the brightness of the image. Multiplies each pixel value by the factor.

```php
public function brightness(float $factor): self
```

| Factor | Effect              |
|--------|---------------------|
| `1.0`  | Original brightness |
| `0.0`  | Completely black    |
| `2.0`  | Double brightness   |

**Returns:** New Image instance with adjusted brightness

**Throws:** `ImageException` if adjustment fails.

### contrast()

Adjust the contrast of the image using a linear transformation.

```php
public function contrast(float $factor): self
```

| Factor | Effect                     |
|--------|----------------------------|
| `1.0`  | Original contrast          |
| `0.0`  | Completely gray (midpoint) |
| `2.0`  | Double contrast            |

**Returns:** New Image instance with adjusted contrast

**Throws:** `ImageException` if adjustment fails.

### linear()

Apply a per-band linear transformation: `output = input × a + b`.

When `a` and `b` are scalars the same values are applied to every band. When `a` and `b` are arrays each element
corresponds to one band and both arrays must have the same length (equal to the number of bands).

```php
public function linear(array|float $a, array|float $b): self
```

**Parameters:**

| Parameter | Type             | Description                                             |
|-----------|------------------|---------------------------------------------------------|
| `$a`      | `float\|float[]` | Multiplier(s). Scalar or array with one value per band. |
| `$b`      | `float\|float[]` | Offset(s). Scalar or array with one value per band.     |

**Returns:** New Image instance with the linear transform applied

**Throws:** `\InvalidArgumentException` when both parameters are arrays with mismatched lengths, `ImageException` if the
operation fails.

**Typical uses:**

- Brightness: `linear(1.2, 0)` — multiply every pixel by 1.2
- Contrast: `linear(1.5, -64)` — scale then shift
- Per-band normalization: `linear([1/s1, 1/s2], [-m1/s1, -m2/s2])`

### saturation()

Adjust the saturation of the image. Converts to HSV, scales saturation, converts back.

```php
public function saturation(float $factor): self
```

| Factor | Effect                    |
|--------|---------------------------|
| `1.0`  | Original saturation       |
| `0.0`  | Grayscale (no saturation) |
| `2.0`  | Double saturation         |

**Returns:** New Image instance with adjusted saturation

**Throws:** `ImageException` if adjustment fails.

### hue()

Adjust the hue of the image. Converts to HSV, shifts the hue channel, converts back.

```php
public function hue(float $degrees): self
```

**Parameters:**

| Parameter  | Type    | Description                                                  |
|------------|---------|--------------------------------------------------------------|
| `$degrees` | `float` | Hue shift in degrees (0–360, where 0 and 360 are equivalent) |

**Returns:** New Image instance with adjusted hue

**Throws:** `ImageException` if adjustment fails.

### gamma()

Apply gamma correction to the image using a power-law transformation.

```php
public function gamma(float $gamma): self
```

| Value   | Effect                 |
|---------|------------------------|
| `< 1.0` | Darkens the image      |
| `1.0`   | Preserves the original |
| `> 1.0` | Brightens the image    |

**Returns:** New Image instance with gamma correction applied

**Throws:** `ImageException` if gamma correction fails.

### sharpen()

Sharpen the image using an unsharp mask. Enhances edges and details.

```php
public function sharpen(?float $sigma = null, ?float $flat = null, ?float $jagged = null): self
```

**Parameters:**

| Parameter | Type     | Default | Description                                       |
|-----------|----------|---------|---------------------------------------------------|
| `$sigma`  | `?float` | `null`  | Sigma for the Gaussian blur (optional)            |
| `$flat`   | `?float` | `null`  | Flat region threshold (`m1` parameter) (optional) |
| `$jagged` | `?float` | `null`  | Edge threshold (`m2` parameter) (optional)        |

**Returns:** New Image instance with sharpening applied

**Throws:** `ImageException` if sharpening fails.

### blur()

Apply Gaussian blur to the image. Convolves with a Gaussian kernel of the specified sigma. Larger sigma values create
more blur.

```php
public function blur(float $sigma): self
```

**Parameters:**

| Parameter | Type    | Description                               |
|-----------|---------|-------------------------------------------|
| `$sigma`  | `float` | Standard deviation of the Gaussian kernel |

**Returns:** New Image instance with blur applied

**Throws:** `ImageException` if blur fails.

### medianBlur()

Apply median blur to the image. Replaces each pixel with the median value of its neighborhood. Effective for removing
salt-and-pepper noise while preserving edges.

```php
public function medianBlur(int $size = 3): self
```

**Parameters:**

| Parameter | Type  | Default | Description                             |
|-----------|-------|---------|-----------------------------------------|
| `$size`   | `int` | `3`     | Size of the square kernel (must be odd) |

**Returns:** New Image instance with median blur applied

**Throws:** `ImageException` if median blur fails.

### invert()

Invert the colors of the image. Each pixel value is replaced with its inverse (`max_value - pixel_value`). For 8-bit
images, this is `255 - pixel_value`.

```php
public function invert(): self
```

**Returns:** New Image instance with inverted colors

**Throws:** `ImageException` if inversion fails.

**Examples:**

```php
// Brightness and contrast
$img = $img->brightness(1.2);
$img = $img->contrast(1.5);

// Saturation and hue
$img = $img->saturation(1.5);
$img = $img->hue(180);  // Shift hue by 180 degrees

// Gamma correction
$img = $img->gamma(0.8);

// Sharpening and blur
$sharp = $img->sharpen(sigma: 1.5);
$blurred = $img->blur(3.0);
$med = $img->medianBlur(5);

// Invert colors (negative effect)
$neg = $img->invert();

// Per-band linear transform
$img = $img->linear(1.2, 0);  // brightness
$img = $img->linear(1.5, -64);  // contrast
```

---

## Normalization

### normalize()

Normalize image pixel values using mean and standard deviation. Applies: `(pixel - mean) / std` for each channel. If
mean/std arrays have length 1, the same value is applied to all channels.

```php
public function normalize(array $mean, array $std): self
```

**Parameters:**

| Parameter | Type    | Description                                                      |
|-----------|---------|------------------------------------------------------------------|
| `$mean`   | `array` | Mean values for each channel (length = bands or 1)               |
| `$std`    | `array` | Standard deviation values for each channel (length = bands or 1) |

**Returns:** New Image instance with normalized pixel values (cast to FLOAT)

**Throws:** `\InvalidArgumentException` if mean/std arrays have invalid length, `ImageException` if normalization fails.

**Examples:**

```php
// ImageNet normalization (RGB)
$normalized = $img->normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]);

// Single value for all channels
$normalized = $img->normalize([0.5], [0.5]);

// Convert back to array for ML pipeline
$array = $normalized->toArray(ChannelFormat::CHW);
```

---

## Band Operations

### get()

Extract a single band (channel) from the image.

```php
public function get(int $index): self
```

**Parameters:**

| Parameter | Type  | Description                             |
|-----------|-------|-----------------------------------------|
| `$index`  | `int` | Zero-based index of the band to extract |

**Returns:** New Image instance containing only the specified band

**Throws:** `\InvalidArgumentException` if the band index is out of range, `ImageException` if extraction fails.

### split()

Split the image into its individual bands (channels).

```php
public function split(): array
```

**Returns:** Array of Image instances, one for each band.

**Throws:** `ImageException` if splitting fails.

### merge()

Merge multiple single-band images into a multi-band image.

```php
public static function merge(array $bands): self
```

**Parameters:**

| Parameter | Type       | Description                                   |
|-----------|------------|-----------------------------------------------|
| `$bands`  | `static[]` | Array of single-band Image instances to merge |

**Returns:** New Image instance with merged bands

**Throws:** `\InvalidArgumentException` if bands array is empty or contains non-Image instances, `ImageException` if
merging fails.

### reorder()

Reorder the bands (channels) of the image.

```php
public function reorder(array $order): self
```

**Parameters:**

| Parameter | Type    | Description                                                  |
|-----------|---------|--------------------------------------------------------------|
| `$order`  | `int[]` | Array specifying the new order of bands (zero-based indices) |

**Returns:** New Image instance with reordered bands

**Throws:** `\InvalidArgumentException` if any index in the order array is invalid, `ImageException` if reordering
fails.

**Examples:**

```php
// Get the red channel
$red = $img->get(0);

// Split into individual channels
[$r, $g, $b] = $img->split();

// Merge bands back together
$recombined = Image::merge([$r, $g, $b]);

// Reorder to BGR (from RGB)
$bgr = $img->reorder([2, 1, 0]);
```

---

## Math Operations

### multiply()

Multiply this image by another image, pixel-by-pixel.

If the other image has fewer bands, it is automatically broadcast across the bands of this image. Typical use: multiply
an RGB image by a single-band mask.

```php
public function multiply(self $other): self
```

**Parameters:**

| Parameter | Type   | Description              |
|-----------|--------|--------------------------|
| `$other`  | `self` | The image to multiply by |

**Returns:** New Image instance with the multiplication applied

**Throws:** `ImageException` if the operation fails.

### add()

Add another image to this one, pixel-by-pixel.

```php
public function add(self $other): self
```

**Parameters:**

| Parameter | Type   | Description         |
|-----------|--------|---------------------|
| `$other`  | `self` | The image to add    |

**Returns:** New Image instance with the addition applied

**Throws:** `ImageException` if the operation fails.

### subtract()

Subtract another image from this one, pixel-by-pixel.

```php
public function subtract(self $other): self
```

**Parameters:**

| Parameter | Type   | Description              |
|-----------|--------|--------------------------|
| `$other`  | `self` | The image to subtract    |

**Returns:** New Image instance with the subtraction applied

**Throws:** `ImageException` if the operation fails.

### divide()

Divide this image by another image, pixel-by-pixel.

```php
public function divide(self $other): self
```

**Parameters:**

| Parameter | Type   | Description            |
|-----------|--------|------------------------|
| `$other`  | `self` | The image to divide by |

**Returns:** New Image instance with the division applied

**Throws:** `ImageException` if the operation fails.

**Examples:**

```php
// Multiply by a single-band mask (broadcast to RGB)
$result = $img->multiply($mask);

// Add two images
$combined = $img1->add($img2);

// Subtract background
$diff = $img->subtract($background);

// Divide by a flat-field correction
$corrected = $img->divide($flatField);
```

---

## Compositing / Drawing

### composite()

Composite an overlay image onto this image. Places the overlay at the specified position using the given blend mode.

```php
public function composite(self $overlay, int $x = 0, int $y = 0, string $blendMode = 'over'): self
```

**Parameters:**

| Parameter    | Type     | Default  | Description                           |
|--------------|----------|----------|---------------------------------------|
| `$overlay`   | `self`   | —        | The overlay image to composite        |
| `$x`         | `int`    | `0`      | X-coordinate for the overlay position |
| `$y`         | `int`    | `0`      | Y-coordinate for the overlay position |
| `$blendMode` | `string` | `'over'` | Blend mode to use                     |

| Common blend modes | Description    |
|--------------------|----------------|
| `'over'`           | Over (default) |
| `'in'`             | In             |
| `'out'`            | Out            |
| `'atop'`           | Atop           |
| `'xor'`            | XOR            |
| `'plus'`           | Plus           |
| `'minus'`          | Minus          |
| `'multiply'`       | Multiply       |
| `'screen'`         | Screen         |

**Returns:** New Image instance with the overlay composited

**Throws:** `ImageException` if compositing fails.

### drawRect()

Draw a rectangle on the image.

```php
public function drawRect(int $left, int $top, int $width, int $height, Color $color, bool $fill = false): self
```

**Parameters:**

| Parameter | Type    | Default | Description                                          |
|-----------|---------|---------|------------------------------------------------------|
| `$left`   | `int`   | —       | X-coordinate of the top-left corner                  |
| `$top`    | `int`   | —       | Y-coordinate of the top-left corner                  |
| `$width`  | `int`   | —       | Width of the rectangle                               |
| `$height` | `int`   | —       | Height of the rectangle                              |
| `$color`  | `Color` | —       | Color of the rectangle                               |
| `$fill`   | `bool`  | `false` | Whether to fill the rectangle (false = outline only) |

**Returns:** New Image instance with the rectangle drawn

**Throws:** `ImageException` if drawing fails.

### drawCircle()

Draw a circle on the image.

```php
public function drawCircle(int $cx, int $cy, int $radius, Color $color, bool $fill = false): self
```

**Parameters:**

| Parameter | Type    | Default | Description                                       |
|-----------|---------|---------|---------------------------------------------------|
| `$cx`     | `int`   | —       | X-coordinate of the circle center                 |
| `$cy`     | `int`   | —       | Y-coordinate of the circle center                 |
| `$radius` | `int`   | —       | Radius of the circle                              |
| `$color`  | `Color` | —       | Color of the circle                               |
| `$fill`   | `bool`  | `false` | Whether to fill the circle (false = outline only) |

**Returns:** New Image instance with the circle drawn

**Throws:** `ImageException` if drawing fails.

### drawLine()

Draw a 1-pixel-wide line between two coordinates on the image.

```php
public function drawLine(int $x1, int $y1, int $x2, int $y2, Color $color): self
```

**Parameters:**

| Parameter | Type    | Description                     |
|-----------|---------|---------------------------------|
| `$x1`     | `int`   | X-coordinate of the start point |
| `$y1`     | `int`   | Y-coordinate of the start point |
| `$x2`     | `int`   | X-coordinate of the end point   |
| `$y2`     | `int`   | Y-coordinate of the end point   |
| `$color`  | `Color` | Color of the line               |

**Returns:** New Image instance with the line drawn

**Throws:** `ImageException` if drawing fails.

### drawMask()

Draw ink onto the image using a single-band 8-bit image as a stencil mask. The mask controls opacity:

- 0 (black) → fully transparent (preserves original)
- 255 (white) → fully opaque (applies ink color at full strength)
- 1–254 → partial transparency proportional to the value

```php
public function drawMask(self $mask, int $x, int $y, Color $color): self
```

**Parameters:**

| Parameter | Type    | Description                                    |
|-----------|---------|------------------------------------------------|
| `$mask`   | `self`  | Single-band 8-bit mask image used as a stencil |
| `$x`      | `int`   | X-coordinate where the mask is placed          |
| `$y`      | `int`   | Y-coordinate where the mask is placed          |
| `$color`  | `Color` | The ink color to apply through the mask        |

**Returns:** New Image instance with the masked ink drawn

**Throws:** `ImageException` if drawing fails.

### drawText()

Draw text on the image.

```php
public function drawText(string $text, int $x, int $y, ?Color $color = null, ?TextOptions $options = null): self
```

**Parameters:**

| Parameter  | Type           | Default | Description                        |
|------------|----------------|---------|------------------------------------|
| `$text`    | `string`       | —       | The text to draw                   |
| `$x`       | `int`          | —       | X-coordinate for the text position |
| `$y`       | `int`          | —       | Y-coordinate for the text position |
| `$color`   | `?Color`       | `null`  | Color of the text (null = white)   |
| `$options` | `?TextOptions` | `null`  | Optional text rendering options    |

**Returns:** New Image instance with the text drawn

**Throws:** `ImageException` if drawing fails.

**Examples:**

```php
// Composite a watermark
$watermark = Image::text('Watermark', TextOptions::default()->withFontSize(24));
$img = $img->composite($watermark, 10, 10, 'over');

// Draw a filled rectangle
$img = $img->drawRect(50, 50, 200, 100, Color::red(), fill: true);

// Draw an outlined rectangle
$img = $img->drawRect(50, 50, 200, 100, Color::green(), fill: false);

// Draw a circle
$img = $img->drawCircle(200, 200, 50, Color::blue(), fill: true);

// Draw a line
$img = $img->drawLine(0, 0, 100, 100, Color::white());

// Draw text with options
$img = $img->drawText('Hello World', 100, 200, Color::white(),
    TextOptions::default()->withFontSize(32));
```

---

## Export

### toArray()

Export the image as an NDArray. Symmetric with `fromArray()`.

```php
public function toArray(ChannelFormat $channelFormat = ChannelFormat::HWC): NDArray
```

**Parameters:**

| Parameter        | Type            | Default | Description                        |
|------------------|-----------------|---------|------------------------------------|
| `$channelFormat` | `ChannelFormat` | `HWC`   | Channel format of the output array |

**Returns:** NDArray containing the image pixel data

**Throws:** `ImageException` if export fails.

### toMemory()

Export raw pixel data as an FFI CData buffer. Complement of `fromMemory()`.

```php
public function toMemory(): CData
```

**Returns:** Raw pixel buffer as FFI CData pointer

**Throws:** `ImageException` if export fails.

---

## Saving / Encoding

### toFile()

Write the image to a file. The output format is inferred from the file extension. Complement of `fromFile()`.

```php
public function toFile(string $path, ?SaveOptions $options = null): void
```

**Parameters:**

| Parameter  | Type           | Default | Description                                |
|------------|----------------|---------|--------------------------------------------|
| `$path`    | `string`       | —       | Destination file path                      |
| `$options` | `?SaveOptions` | `null`  | Format-specific encoding options           |

**Throws:** `ImageException` if writing the file fails.

### toBuffer()

Encode the image to a binary string in the specified format. Complement of `fromBuffer()`.

```php
public function toBuffer(ImageFormat $format, ?SaveOptions $options = null): string
```

**Parameters:**

| Parameter  | Type           | Default | Description                               |
|------------|----------------|---------|-------------------------------------------|
| `$format`  | `ImageFormat`  | —       | Target image format                       |
| `$options` | `?SaveOptions` | `null`  | Format-specific encoding options          |

**Returns:** Encoded image data as a string

**Throws:** `ImageException` if encoding fails.

**Examples:**

```php
use PhpMlKit\Opal\ImageFormat;
use PhpMlKit\Opal\SaveOptions;

// Save with default options (format from extension)
$img->toFile('output.jpg');

// Save with format-specific options
$img->toFile('output.jpg', SaveOptions::jpeg(quality: 95));
$img->toFile('output.png', SaveOptions::png(compression: 9));
$img->toFile('output.webp', SaveOptions::webp(lossless: true));

// Encode to string
$jpegData = $img->toBuffer(ImageFormat::JPEG, SaveOptions::jpeg(quality: 85));
$pngData = $img->toBuffer(ImageFormat::PNG);
```

---

## Misc

### copy()

Create a copy of the image.

```php
public function copy(): self
```

**Returns:** New Image instance that is a copy of this image

**Throws:** `ImageException` if copying fails.

### dispose()

*Note: Image implements `Disposable` via the `dispose()` method. Call `unset()` or let the variable go out of scope for
standard GC.*

**Examples:**

```php
// Create an independent copy
$copy = $img->copy();
```
