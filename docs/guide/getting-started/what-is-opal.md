---
title: What is phpmlkit/opal?
---

# What is phpmlkit/opal?

phpmlkit/opal is a high-performance image processing library for PHP. It wraps [libvips](https://www.libvips.org) — a fast, memory-efficient C library — through PHP's FFI extension, exposing a clean, type-safe, immutable API.

## Why another image library?

Existing PHP image libraries fall into three categories, none of which are ideal for modern ML and programmatic workflows:

- **GD** — bundled with PHP but slow, limited format support, no alpha compositing, no color management.
- **Imagick** — wraps ImageMagick. Feature-rich but memory-hungry and significantly slower than libvips for bulk operations.
- **Raw jcupitt/vips** — the PHP bindings for libvips are powerful but expose a low-level, procedural API that maps directly to the C interface.

phpmlkit/opal sits above jcupitt/vips and provides:

- **Immutable operations** — every transform returns a new `Image`. No side effects, no mutation.
- **Lazy evaluation** — VIPS pipelines are queued and optimized; execution happens only when data is consumed.
- **NDArray interop** — convert images to and from [phpmlkit/ndarray](https://github.com/phpmlkit/ndarray) for ML pipelines.
- **Type-safe API** — enums, value objects, and full PHP 8.2+ type annotations.
- **Drawing helpers** — rects, circles, text, compositing. No separate graphics library.

## Relationship with libvips

The library does **not** replace libvips. It wraps it.

```
┌─────────────────────────────┐
│     phpmlkit/opal          │  ← Immutable, lazy, typed API
├─────────────────────────────┤
│     jcupitt/vips (PHP FFI)  │  ← Low-level FFI bindings
├─────────────────────────────┤
│     libvips (C library)     │  ← Image processing engine
└─────────────────────────────┘
```

libvips is one of the fastest image processing libraries available. It processes only the pixel regions needed for the final output, keeps intermediate results small, and can pipeline operations without allocating temporary buffers. phpmlkit/opal preserves these characteristics while providing a developer-friendly interface.

## When to use phpmlkit/opal

You should use this library when you need to:

- **Process images programmatically** — resize, crop, rotate, filter, convert color spaces.
- **Build ML preprocessing pipelines** — resize → normalize → export NDArray for inference.
- **Annotate images** — draw bounding boxes, labels, heatmaps for object detection or segmentation visualisation.
- **Generate thumbnails at scale** — libvips shrink-on-load is significantly faster than load-then-resize.
- **Compose images** — overlay graphics, watermarks, text with blend modes.
- **Convert between formats** — JPEG ↔ PNG ↔ WebP ↔ AVIF with quality control.

## When to use raw libvips

You might prefer the raw jcupitt/vips bindings when you need:

- **Direct access to libvips operations** not yet exposed by phpmlkit/opal.
- **Custom VIPS pipelines** built dynamically with the low-level API.
- **Maximum control over VIPS configuration** — cache settings, concurrency, memory limits.

In those cases you can still use phpmlkit/opal alongside raw vips code. The `Image` class is a thin wrapper; you can access the underlying `VipsImage` via the public `$vipsImage` property if needed.
