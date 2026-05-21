---
title: NDArray Interop
---

# NDArray Interop

phpmlkit/opal integrates directly with [phpmlkit/ndarray](https://github.com/phpmlkit/ndarray) for ML workflows. You can export image data as NDArrays for model inference and import prediction results back as images.

## Exporting: `toArray()`

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ChannelFormat;

$image = Image::fromFile('photo.jpg');

// [H, W, C] — TensorFlow / NumPy default
$hwc = $image->toArray(ChannelFormat::HWC);

// [C, H, W] — PyTorch / ONNX vision default
$chw = $image->toArray(ChannelFormat::CHW);
```

The returned NDArray has:

- **dtype** matching the image's `BandFormat` (usually `uint8`).
- **shape** determined by the channel format and image dimensions.

## Importing: `fromArray()`

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\ChannelFormat;
use PhpMlKit\NDArray\NDArray;

// From a raw NDArray
$array = NDArray::zeros([224, 224, 3]);
$image = Image::fromArray($array, ColorSpace::RGB, ChannelFormat::HWC);

// From a PHP array
$image = Image::fromArray($pixels, ColorSpace::RGB, ChannelFormat::HWC);
```

`fromArray` accepts both `NDArray` instances and plain PHP arrays. Arrays are cast to `uint8` NDArrays internally.

## Channel formats

The `ChannelFormat` enum controls how multi-channel data is laid out in memory:

| Format | Shape | Frameworks |
|--------|-------|------------|
| `ChannelFormat::HWC` | `[height, width, channels]` | TensorFlow, NumPy, OpenCV, PIL |
| `ChannelFormat::CHW` | `[channels, height, width]` | PyTorch, ONNX, cuDNN |

```php
// HWC (height, width, channels) — row-major, per-pixel interleaved
//   [0, 0, :] = R, G, B of pixel (0,0)
//   [0, 1, :] = R, G, B of pixel (0,1)

// CHW (channels, height, width) — planar
//   [0, :, :] = all red channel values
//   [1, :, :] = all green channel values
```

When `CHW` is requested, `toArray()` internally permutes the dimensions. When `CHW` is passed to `fromArray()`, the array is permuted back to HWC before constructing the VIPS image.

## Band formats

The `BandFormat` enum maps between VIPS band formats and NDArray dtypes:

| `BandFormat` | NDArray `DType` | Description |
|-------------|-----------------|-------------|
| `UCHAR` | `UInt8` | Unsigned 8-bit (0–255) |
| `USHORT` | `UInt16` | Unsigned 16-bit |
| `SHORT` | `Int16` | Signed 16-bit |
| `UINT` | `UInt32` | Unsigned 32-bit |
| `INT` | `Int32` | Signed 32-bit |
| `FLOAT` | `Float32` | 32-bit float |
| `DOUBLE` | `Float64` | 64-bit float |

Cast an image to a different band format before export:

```php
$floatArray = $image->toFloat()->toArray(ChannelFormat::CHW);
// NDArray is now Float32 instead of UInt8
```

## ML preprocessing pipeline

The typical pattern for preparing images for model inference:

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ChannelFormat;
use PhpMlKit\NDArray\NDArray;

function preprocessForInference(Image $image, int $inputSize = 224): NDArray
{
    $scale = min($inputSize / $image->width(), $inputSize / $image->height());

    return $image
        ->scale($scale)
        ->centerCrop($inputSize, $inputSize)
        ->toFloat()
        ->normalize(
            mean: [0.485, 0.456, 0.406],  // ImageNet stats
            std:  [0.229, 0.224, 0.225],
        )
        ->toArray(ChannelFormat::CHW);     // PyTorch: [3, 224, 224]
}

$image = Image::fromFile('cat.jpg');
$tensor = preprocessForInference($image);

printf("Shape: [%s]\n", implode(', ', $tensor->shape()));
// Shape: [3, 224, 224]
```

All operations except `toArray()` are lazy — VIPS fuses the resize, crop, cast, and normalize into a single optimised pipeline that executes only when the array is exported.

## Converting predictions back to images

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\ChannelFormat;
use PhpMlKit\NDArray\NDArray;
use PhpMlKit\NDArray\DType;

// Denormalise from ImageNet stats
$denormalized = $predictions
    ->multiply(nd_array([0.229, 0.224, 0.225]))
    ->add(nd_array([0.485, 0.456, 0.406]))
    ->clip(0, 1)
    ->multiply(255)
    ->astype(DType::UInt8);

// Back to image
$result = Image::fromArray($denormalized, ColorSpace::RGB, ChannelFormat::CHW);
$result->toFile('output.png');
```

## Memory-efficient batch processing

```php
$images = [];
foreach ($filenames as $path) {
    $img = Image::fromFile($path);
    $tensor = preprocessForInference($img);
    $img->dispose();  // free VIPS resources immediately
    $images[] = $tensor;
}

// Stack into batch: [batch, channels, height, width]
$batch = NDArray::stack($images);
printf("Batch shape: [%s]\n", implode(', ', $batch->shape()));
```

## Summary

| Method | Direction | Formats | Notes |
|--------|-----------|---------|-------|
| `toArray()` | Image → NDArray | HWC, CHW | Dtype matches band format |
| `fromArray()` | NDArray → Image | HWC, CHW | Auto-casts plain arrays to uint8 |
| `toBuffer()` | Image → encoded bytes | JPEG, PNG, WebP, … | Format + save options |
| `fromBuffer()` | bytes → Image | auto-detected | From HTTP responses, streams |
