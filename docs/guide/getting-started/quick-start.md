---
title: Quick Start
---

# Quick Start

## Loading an image

Use `Image::fromFile()` to load from disk. The format is detected automatically from the file header.

```php
use PhpMlKit\Opal\Image;

$image = Image::fromFile('photo.jpg');
```

Load from a binary string (e.g. from an HTTP response or database):

```php
$bytes = file_get_contents('photo.jpg');
$image = Image::fromBuffer($bytes);
```

Load with options — shrink-on-load for large images, or skip EXIF auto-rotation:

```php
use PhpMlKit\Opal\LoadOptions;

$image = Image::fromFile('large.tiff', LoadOptions::default()
    ->withShrink(2)
    ->withAutoRotate(false)
);
```

## Properties

```php
printf("Dimensions: %dx%d\n", $image->width(), $image->height());
printf("Bands: %d\n", $image->bands());        // 3 for RGB, 4 for RGBA, 1 for grayscale
printf("Color space: %s\n", $image->colorSpace()->name);
printf("Has alpha: %s\n", $image->hasAlpha() ? 'yes' : 'no');
printf("Band format: %s\n", $image->bandFormat()->name);
```

## Basic transforms

All transforms return a new `Image`. The original is never modified.

```php
use PhpMlKit\Opal\Kernel;
use PhpMlKit\Opal\FlipDirection;

// Resize
$resized = $image->resize(800, 600, Kernel::Lanczos3);
$scaled  = $image->scale(0.5);
$toWidth = $image->resizeToWidth(400);

// Thumbnail via shrink-on-load (fastest for thumbnails)
$thumb = Image::thumbnail('photo.jpg', 300);

// Crop
$cropped       = $image->crop(100, 50, 400, 300);
$centerCropped = $image->centerCrop(224, 224);

// Rotate and flip
$rotated  = $image->rotate(45);
$rotated90 = $image->rot90();
$flipped  = $image->flip(FlipDirection::Horizontal);
```

## Color adjustments

```php
$adjusted = $image
    ->brightness(1.2)      // 1.0 = unchanged
    ->contrast(1.5)        // 1.0 = unchanged
    ->saturation(1.3)      // 0 = grayscale, 1 = unchanged
    ->gamma(2.2);          // power-law correction
```

## Filters

```php
$sharpened = $image->sharpen(sigma: 2.0);
$blurred   = $image->blur(sigma: 3.0);
$denoised  = $image->medianBlur(5);
$inverted  = $image->invert();
```

## Color space conversion

```php
$gray = $image->toGrayscale();
$rgb  = $image->toRGB();
$rgba = $image->toRGBA();
$bgr  = $image->toBGR();
```

## Saving

The format is inferred from the file extension.

```php
use PhpMlKit\Opal\SaveOptions;

// JPEG
$image->toFile('output.jpg', SaveOptions::jpeg(quality: 90, strip: true));

// PNG
$image->toFile('output.png', SaveOptions::png(compression: 9));

// WebP
$image->toFile('output.webp', SaveOptions::webp(quality: 80, lossless: false));

// AVIF
$image->toFile('output.avif', SaveOptions::avif(quality: 50, speed: 5));
```

Encode to a string instead of writing to disk:

```php
use PhpMlKit\Opal\ImageFormat;

$pngBytes = $image->toBuffer(ImageFormat::PNG, SaveOptions::png(compression: 6));
```

## NDArray export

Convert an image to an NDArray for ML workflows:

```php
use PhpMlKit\Opal\ChannelFormat;

// PyTorch format: [C, H, W]
$array = $image->toArray(ChannelFormat::CHW);

// TensorFlow format: [H, W, C]
$array = $image->toArray(ChannelFormat::HWC);

printf("Shape: [%s]\n", implode(', ', $array->shape()));
```

Convert an NDArray back to an image:

```php
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\NDArray\NDArray;

$array = NDArray::zeros([224, 224, 3]);
$image = Image::fromArray($array, ColorSpace::RGB, ChannelFormat::HWC);
```

## Complete example

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\Kernel;
use PhpMlKit\Opal\SaveOptions;
use PhpMlKit\Opal\ChannelFormat;

// Load
$img = Image::fromFile('photo.jpg');

// Process
$result = $img
    ->resize(800, 600, Kernel::Lanczos3)
    ->sharpen(sigma: 1.5)
    ->saturation(1.1)
    ->drawRect(50, 50, 200, 100, Color::red(), fill: false)
    ->drawText('Hello', 60, 70, color: Color::white());

// Save
$result->toFile('output.jpg', SaveOptions::jpeg(quality: 90, strip: true));

// Export
$array = $result->toArray(ChannelFormat::CHW);

printf("Done — shape: [%s]\n", implode(', ', $array->shape()));
```
