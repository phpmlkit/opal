<p align="center">
  <img src="docs/public/logo.png" width="200" height="200" alt="Opal Logo">
</p>

<h1 align="center">Opal</h1>

<p align="center">High-performance image processing for PHP, backed by libvips via FFI</p>

<p align="center">
  <a href="https://packagist.org/packages/phpmlkit/opal"><img src="https://img.shields.io/packagist/v/phpmlkit/opal?style=flat-square" alt="Latest Version"></a>
  <a href="https://github.com/phpmlkit/opal/actions"><img src="https://img.shields.io/github/actions/workflow/status/phpmlkit/opal/tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Workflow Status"></a>
  <a href="https://packagist.org/packages/phpmlkit/opal"><img src="https://img.shields.io/packagist/dt/phpmlkit/opal?style=flat-square" alt="Total Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License"></a>
</p>

## Features

- **Lazy Evaluation**: All image operations are lazy and only executed when needed (saving, encoding, exporting)
- **Immutable Operations**: Every transformation returns a new image instance
- **NDArray Integration**: Seamless interoperability with `phpmlkit/ndarray` for ML workflows
- **Comprehensive API**: Support for all common image operations
- **Type-Safe**: Full PHP 8.2+ type safety with enums and value objects
- **Memory Efficient**: Zero-copy operations where possible

## Performance

**Load → resize → rotate 45° → sharpen** (full pipeline):

| Image     | GD        | Imagick     | Opal         |
|-----------|-----------|-------------|--------------|
| 640×480   | 8.48 ms   | 128.55 ms   | **1.56 ms**  |
| 1920×1080 | 49.67 ms  | 721.97 ms   | **5.36 ms**  |
| 4000×2670 | 152.74 ms | 2,039.15 ms | **15.47 ms** |
| 6000×4000 | 299.43 ms | 3,538.47 ms | **33.17 ms** |

*Apple M1, macOS. Run: `php benchmarks/opal-vs-gd-vs-imagick.php`*

## Installation

```bash
composer require phpmlkit/opal
```

## Quick Start

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Kernel;

// Load an image
$image = Image::fromFile('path/to/image.jpg');

// Resize with high-quality kernel
$resized = $image->resize(800, 600, Kernel::Lanczos3);

// Convert to grayscale
$grayscale = $resized->toGrayscale();

// Save the result
$grayscale->toFile('output.jpg');
```

## Core Concepts

### Lazy Evaluation

All image operations are lazy - they build a pipeline that's only executed when you call a terminal operation:

```php
// This doesn't process anything yet
$pipeline = $image
    ->resize(800, 600)
    ->toGrayscale()
    ->brightness(1.2);

// This triggers the actual processing
$pipeline->toFile('output.jpg');
```

### Immutable Operations

Every transformation returns a new image instance:

```php
$original = Image::fromFile('input.jpg');
$modified = $original->resize(800, 600);

// $original is unchanged
$original->toFile('original.jpg');
$modified->toFile('resized.jpg');
```

## Usage Examples

### Basic Image Operations

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Kernel;
use PhpMlKit\Opal\FlipDirection;

// Load image
$image = Image::fromFile('photo.jpg');

// Resize
$resized = $image->resize(1920, 1080);
$scaled = $image->scale(0.5);

// Thumbnail via shrink-on-load — faster than load+resize
$thumbnail = Image::thumbnail('photo.jpg', 300);

// Crop
$cropped = $image->crop(100, 100, 800, 600);
$centerCropped = $image->centerCrop(800, 600);

// Flip and rotate
$flipped = $image->flip(FlipDirection::Horizontal);
$rotated = $image->rotate(45);
$rotated90 = $image->rot90();

// Save
$resized->toFile('resized.jpg');
```

### Color Space Conversion

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ColorSpace;

$image = Image::fromFile('photo.jpg');

// Convert to grayscale
$grayscale = $image->toGrayscale();

// Convert to RGB
$rgb = $grayscale->toRGB();

// Add alpha channel
$rgba = $image->toRGBA();

// Remove alpha
$rgb = $rgba->removeAlpha();
```

### Color Adjustments

```php
use PhpMlKit\Opal\Image;

$image = Image::fromFile('photo.jpg');

// Brightness (1.0 = unchanged, >1 = brighter, <1 = darker)
$brighter = $image->brightness(1.2);

// Contrast (1.0 = unchanged, >1 = more contrast, <1 = less contrast)
$highContrast = $image->contrast(1.5);

// Saturation (0 = grayscale, 1.0 = unchanged, >1 = more saturated)
$vivid = $image->saturation(1.3);

// Hue rotation
$hueShifted = $image->hue(90);

// Gamma correction
$gammaCorrected = $image->gamma(2.2);
```

### Filters and Effects

```php
use PhpMlKit\Opal\Image;

$image = Image::fromFile('photo.jpg');

// Sharpen
$sharpened = $image->sharpen(sigma: 2.0);

// Blur
$blurred = $image->blur(sigma: 3.0);

// Median blur (good for noise reduction)
$denoised = $image->medianBlur(5);

// Invert colors
$inverted = $image->invert();
```

### Drawing and Compositing

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\BoundingBox;

$image = Image::fromFile('photo.jpg');

// Draw rectangle
$withRect = $image->drawRect(
    100, 100, 200, 150,
    Color::red()
);

// Draw circle
$withCircle = $image->drawCircle(
    400, 300, 50,
    Color::blue(),
    fill: true
);

// Draw text
$withText = $image->drawText(
    'Hello World',
    100, 100,
    color: Color::white()
);

// Composite images
$overlay = Image::fromFile('overlay.png');
$composited = $image->composite($overlay, 50, 50);
```

### NDArray Interoperability

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\NDArray\NDArray;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\ChannelFormat;

// Convert image to NDArray
$image = Image::fromFile('photo.jpg');
$array = $image->toArray(ChannelFormat::HWC);  // [H, W, C]

// Convert NDArray to image
$array = NDArray::zeros([224, 224, 3]);
$image = Image::fromArray($array, ColorSpace::RGB, ChannelFormat::HWC);
```

### Advanced Options

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\LoadOptions;
use PhpMlKit\Opal\SaveOptions;

// Load with options
$image = Image::fromFile('photo.jpg', LoadOptions::default()
    ->withAutoRotate(true)
    ->withShrink(2)
);

// Save with options
$image->toFile('output.jpg', SaveOptions::jpeg(
    quality: 90,
    strip: true,
    progressive: true
));

// PNG with compression
$image->toFile('output.png', SaveOptions::png(
    compression: 9,
    interlace: true
));

// WebP with lossless
$image->toFile('output.webp', SaveOptions::webp(
    quality: 80,
    lossless: false
));
```

## API Reference

### Value Objects

#### `Color`

Immutable RGBA color representation.

```php
$red = Color::rgb(255, 0, 0);
$blue = Color::fromHex('#0000ff');
$white = Color::white();
$transparent = Color::transparent();
```

#### `ImageSize`

Immutable 2D size descriptor.

```php
$size = new ImageSize(1920, 1080);
$aspectRatio = $size->aspectRatio();
$pixels = $size->pixels();
```

#### `BoundingBox`

Immutable axis-aligned bounding box.

```php
$box = new BoundingBox(10, 10, 100, 50);
$iou = $box->iou($otherBox);
$clamped = $box->clamp($imageWidth, $imageHeight);
```

### Enums

- `ColorSpace`: RGB, RGBA, BGR, BGRA, Grayscale, Lab, HSV, CMYK
- `ImageFormat`: JPEG, PNG, WebP, TIFF, GIF, BMP, AVIF, HEIF
- `Kernel`: Nearest, Linear, Cubic, Mitchell, Lanczos2, Lanczos3, Mks211, Mks213
- `FlipDirection`: Horizontal, Vertical, Both
- `BandFormat`: UChar, UInt16, Float, Double
- `ChannelFormat`: HWC, CHW

### Options Classes

#### `LoadOptions`

Control image loading behavior.

```php
$options = LoadOptions::default()
    ->withPage(0)
    ->withAutoRotate(true)
    ->withShrink(2);
```

#### `SaveOptions`

Control image saving behavior.

```php
$options = SaveOptions::jpeg(quality: 90, strip: true);
$options = SaveOptions::png(compression: 9);
$options = SaveOptions::webp(quality: 80, lossless: false);
```

## Performance Tips

1. **Use lazy evaluation**: Chain operations together before saving
2. **Use shrink-on-load**: Load with `Image::thumbnail()` or `LoadOptions::withShrink()` for large images
3. **Choose appropriate interpolation**: Use `Lanczos` for quality, `Bilinear` for speed
4. **Dispose explicitly**: Call `dispose()` when done with large images

## Documentation

- [Full Documentation](https://phpmlkit.github.io/opal/)
- [What is Opal?](https://phpmlkit.github.io/opal/guide/getting-started/what-is-opal)
- [Quick Start](https://phpmlkit.github.io/opal/guide/getting-started/quick-start)
- [API Reference](https://phpmlkit.github.io/opal/api/)
- [Installation](https://phpmlkit.github.io/opal/guide/getting-started/installation)

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer lint

# Format code
composer cs:fix
```

## Requirements

- PHP 8.2 or higher
- ext-ffi extension

## License

MIT

## Credits

- [libvips](https://www.libvips.org) — image processing engine
- [jcupitt/vips](https://github.com/jcupitt/php-vips) — PHP FFI bindings for libvips
- [phpmlkit/ndarray](https://github.com/phpmlkit/ndarray) — numerical computing for ML workflows
