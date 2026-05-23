---
title: What is phpmlkit/opal?
---

# What is phpmlkit/opal?

phpmlkit/opal is a high-performance image processing library for PHP. It's an alternative to GD and Imagick — faster,
more memory-efficient, and designed for modern workflows.

```php
use PhpMlKit\Opal\Image;

$img = Image::fromFile('photo.jpg')
    ->resize(1200, 800)
    ->sharpen()
    ->toFile('output.jpg');
```

## Why Opal?

GD is limited and slow. Imagick is powerful but memory-hungry. Opal is built on [libvips](https://www.libvips.org) — a C
engine that processes images faster than both while using a fraction of the memory.

**Load → resize → rotate 45° → sharpen** (full pipeline):

| Image       | GD        | Imagick     | Opal         |
|-------------|-----------|-------------|--------------|
| 640×480     | 8.48 ms   | 128.55 ms   | **1.56 ms**  |
| 1920×1080   | 49.67 ms  | 721.97 ms   | **5.36 ms**  |
| 4000×2670   | 152.74 ms | 2,039.15 ms | **15.47 ms** |
| 6000×4000   | 299.43 ms | 3,538.47 ms | **33.17 ms** |

> *Apple M1, macOS. 5–30 iterations per cell. Run: `php benchmarks/opal-vs-gd-vs-imagick.php`*

Opal is **5–10× faster than GD** and **50–105× faster than Imagick**.

## What you get

- **Immutable API** — every transform returns a new `Image`. No mutation, no surprises.
- **Lazy pipelines** — build a chain of operations; nothing executes until you save or export. VIPS fuses and optimises
  the pipeline automatically.
- **Full format support** — JPEG, PNG, WebP, AVIF, TIFF, HEIF, BMP, GIF. Load from files, buffers, or raw memory.
- **Drawing & compositing** — rectangles, circles, text, image overlays with blend modes. No separate graphics library
  needed.
- **ML-ready** — export images as NDArrays for PyTorch (CHW) or TensorFlow (HWC) format. Preprocessing pipelines compose
  directly with [phpmlkit/ndarray](https://github.com/phpmlkit/ndarray).
- **Type-safe** — PHP 8.2+ with strict types, enums, and immutable value objects throughout.

## When to use it

- Resizing, cropping, rotating, filtering, and colour conversions
- ML preprocessing — resize → normalize → tensor export
- Thumbnail generation at scale (libvips shrink-on-load)
- Drawing annotations — bounding boxes, labels, heatmaps
- Compositing — overlays, watermarks, text with blend modes
- Format conversion with quality control

## Requirements

- PHP 8.2+
- `ext-ffi`

Everything else — including the libvips binary — is installed automatically by Composer.
