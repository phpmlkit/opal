# Changelog

All notable changes to `phpmlkit/opal` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.1.0 - 2026-06-20

### What's Changed

* feat: add applyMask method to Image by @CodeWithKyrian in https://github.com/phpmlkit/opal/pull/1
* feat: add pixel-wise math operations by @CodeWithKyrian in https://github.com/phpmlkit/opal/pull/2
* fix: match Color band count to image in all libvips call sites by @CodeWithKyrian in https://github.com/phpmlkit/opal/pull/3
* fix: rewrite color space conversion to actually transform pixels by @CodeWithKyrian in https://github.com/phpmlkit/opal/pull/4
* feat: add opt-in alpha blending to draw operations by @CodeWithKyrian in https://github.com/phpmlkit/opal/pull/5
* feat: rewrite TextOptions font API around unified withFont method by @CodeWithKyrian in https://github.com/phpmlkit/opal/pull/6

**Full Changelog**: https://github.com/phpmlkit/opal/compare/1.0.0...1.1.0

## v1.0.0 - 2026-05-23

Initial release of **phpmlkit/opal** — a high-performance image processing library for PHP, built on libvips.

### What is Opal?

Opal is an alternative to GD and Imagick. It wraps libvips through PHP's FFI extension, providing a modern, immutable, type-safe API that's 5–10× faster than GD and 50–105× faster than Imagick in real-world pipelines.

### Features

- **Lazy pipelines** — operations are queued and optimised; execution happens only when you save or export
- **Immutable API** — every transform returns a new `Image`, no side effects
- **Full format support** — JPEG, PNG, WebP, AVIF, TIFF, HEIF, BMP, GIF
- **ML-ready NDArray interop** — export images in CHW (PyTorch) or HWC (TensorFlow) format for [phpmlkit/ndarray](https://github.com/phpmlkit/ndarray)
- **Drawing & compositing** — rectangles, circles, lines, text, and image overlays with blend modes — no separate graphics library needed
- **8 resampling kernels** — Nearest, Linear, Cubic, Mitchell, Lanczos2, Lanczos3, Mks211, Mks213
- **8 colour spaces** — RGB, RGBA, BGR, BGRA, Grayscale, Lab, HSV, CMYK
- **Image metadata** — EXIF, ICC profiles, resolution, page count
- **Type-safe** — PHP 8.2+ with strict types, enums, and immutable value objects
- 

### Installation

```bash
composer require phpmlkit/opal


```
Requirements: PHP 8.2+, ext-ffi. The libvips binary is downloaded automatically.

### Credits

- [libvips](https://www.libvips.org) — the underlying image processing engine
- [jcupitt/vips](https://github.com/jcupitt/php-vips) — PHP FFI bindings for libvips
