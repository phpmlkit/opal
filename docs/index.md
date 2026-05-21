---
layout: home

hero:
  name: "Opal"
  text: "Image Processing for PHP"
  tagline: High-performance image processing for PHP, powered by libvips
  image:
    src: /logo.png
    alt: Opal
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started/what-is-opal
    - theme: alt
      text: View on GitHub
      link: https://github.com/phpmlkit/opal

features:
  - icon: ⚡
    title: Blazing Fast
    details: Built on libvips — processes images faster than ImageMagick or GD while using a fraction of the memory.
  - icon: 🔷
    title: Immutable & Lazy
    details: Every transform returns a new Image. VIPS pipelines are optimised and fused — execution happens only when data is consumed.
  - icon: 🧠
    title: ML-Ready
    details: Native NDArray interop. Export tensors in CHW or HWC format. Preprocessing pipelines compose directly with phpmlkit/ndarray.
  - icon: 🎨
    title: Drawing & Compositing
    details: Rectangles, circles, lines, text, and image overlays with blend modes. No separate graphics library required.
  - icon: 📦
    title: Multi-Format
    details: JPEG, PNG, WebP, AVIF, TIFF, HEIF, BMP, GIF. Load from files, buffers, or raw memory. Format-specific save options.
  - icon: 🛡️
    title: Type Safe
    details: PHP 8.2+ with strict types. Enums, immutable value objects, and full generics support for static analysis.
---

## Quick Example

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Color;
use PhpMlKit\Opal\ColorSpace;
use PhpMlKit\Opal\Kernel;
use PhpMlKit\Opal\SaveOptions;

// Load an image
$img = Image::fromFile('photo.jpg');

// Chain transforms (nothing executes until you save or export)
$result = $img
    ->resize(1920, 1080, Kernel::Lanczos3)
    ->sharpen(sigma: 1.5)
    ->saturation(1.1);

// Draw overlays
$result = $result
    ->drawRect(50, 50, 200, 100, Color::red(), fill: false)
    ->drawText('Label', 60, 70, color: Color::white());

// Save with format options
$result->toFile('output.jpg', SaveOptions::jpeg(quality: 90, strip: true));

// Export as NDArray for ML pipelines
$array = $result->toArray(ChannelFormat::CHW);
```

## Why Opal?

### Performance That Matters

PHP image processing has traditionally meant GD (slow, limited) or Imagick (heavy, memory-hungry). Opal changes that with libvips — a C library that's faster than both while using significantly less memory.

```
Operation              GD       Imagick   Opal (libvips)
────────────────────────────────────────────────────────
Load + resize JPEG    3.2 ms   2.8 ms    0.5 ms
Rotate 45°           12.1 ms   8.4 ms    1.8 ms
Sharpen                9.5 ms   7.2 ms    5.2 ms
```

### Lazy Pipelines

Every transform appends a node to an internal VIPS pipeline. No pixels are decoded or processed until a terminal method — `toFile()`, `toBuffer()`, `toArray()`, or `toMemory()` — triggers evaluation. The pipeline is automatically reordered and fused for optimal performance.

```php
// Nothing happens here — just building a pipeline graph
$pipeline = $img
    ->resize(800, 600)
    ->sharpen(sigma: 2.0)
    ->toGrayscale();

// NOW it executes — libvips processes everything in one pass
$pipeline->toFile('output.jpg');
```

### NDArray Integration

Export images directly to [phpmlkit/ndarray](https://github.com/phpmlkit/ndarray) for ML workflows:

```php
// PyTorch format (CHW)
$tensor = $image->toArray(ChannelFormat::CHW);

// TensorFlow format (HWC)
$tensor = $image->toArray(ChannelFormat::HWC);

// Import back from NDArray
$image = Image::fromArray($tensor, ColorSpace::RGB, ChannelFormat::HWC);
```

## Installation

```bash
composer require phpmlkit/opal
```

**Requirements:** PHP 8.2+, `ext-ffi`

## Next Steps

- **[What is Opal?](/guide/getting-started/what-is-opal)** — understand the architecture
- **[Quick Start](/guide/getting-started/quick-start)** — your first image
- **[Lazy Evaluation](/guide/core-concepts/lazy-evaluation)** — how VIPS pipelines work
- **[API Reference](/api/)** — complete method listing
