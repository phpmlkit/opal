---
title: Lazy Evaluation
---

# Lazy Evaluation

phpmlkit/opal inherits libvips's **lazy evaluation** model. Operations are not executed when you call them — they are queued into a pipeline that runs only when you consume the result.

## The pipeline model

Each transform appends a node to an internal VIPS pipeline graph. The graph is a directed acyclic graph of operations. No pixels are read, processed, or written until a **terminal operation** triggers evaluation.

```php
use PhpMlKit\Opal\Image;

$image = Image::fromFile('large.tiff');  // header only — no pixel data decoded

// These methods build the pipeline but do NOT execute:
$pipeline = $image
    ->resize(1920, 1080)
    ->sharpen(sigma: 2.0)
    ->toGrayscale();
```

At this point, no image processing has happened. The original `$image` and the derived `$pipeline` are both lightweight objects holding VIPS operation references.

## Terminal operations

The pipeline is evaluated when you call any of these methods:

```php
// Save to disk — triggers full pipeline execution
$pipeline->toFile('output.jpg');

// Encode to a binary string
$bytes = $pipeline->toBuffer(ImageFormat::JPEG);

// Export as NDArray (also triggers evaluation)
$array = $pipeline->toArray(ChannelFormat::HWC);

// Export raw pixel buffer
$memory = $pipeline->toMemory();
```

::: tip
You can chain any number of operations before calling a terminal method. VIPS will merge, reorder, and optimise the pipeline automatically.
:::

## Memory benefits

Lazy evaluation dramatically reduces memory usage. Consider this common pattern:

```php
// No image data is held in PHP memory
$result = Image::fromFile('huge.tiff')
    ->resize(1920, 1080)
    ->sharpen(sigma: 2.0)
    ->toFile('output.jpg');
```

libvips streams pixel data through the pipeline in small tiles. It never decodes the full source image into memory. For a 100 MB TIFF resized to a 2 MB JPEG, peak memory usage stays near 2 MB, not 100 MB.

Contrast this with libraries that eagerly decode into a pixel buffer:

```php
// Imagick loads the entire image into memory
$img = new Imagick('huge.tiff');       // 100 MB allocated
$img->resizeImage(1920, 1080, ...);    // another 50 MB allocated
$img->writeImage('output.jpg');        // then freed
```

## When evaluation happens multiple times

Each terminal call traverses the pipeline independently. If you call `save()` and then `toArray()` on the same pipeline, the operations execute twice:

```php
$pipeline = $image->resize(800, 600);

$pipeline->toFile('out.jpg');   // evaluated once
$array = $pipeline->toArray(); // evaluated again
```

If you need both outputs from the same evaluation, work with a single terminal operation:

```php
$pipeline->toFile('out.jpg');   // evaluate once

// Reload from disk if you need both save and array
$saved = Image::fromFile('out.jpg');
$array = $saved->toArray();
```

Or restructure your pipeline to export first, then save the array result if appropriate.

## Forcing evaluation explicitly

You typically do not need to force evaluation. If you want to check that a pipeline is valid without producing output, you can trigger evaluation by exporting raw pixel data:

```php
try {
    $pipeline->toMemory();  // evaluate to validate
} catch (\Exception $e) {
    echo "Pipeline failed: " . $e->getMessage();
}
```

::: warning
Calling `toMemory()` on a heavy pipeline purely for validation will still process the full image. Prefer validating on a downscaled version in development.
:::

## VIPS optimisation notes

The VIPS library applies several optimisations automatically:

- **Operation fusion** — adjacent point operations (brightness, contrast, gamma) are merged into a single linear transform.
- **Tile-based execution** — images are processed in small tiles (typically 128x128) so the working set fits in CPU cache.
- **Region-of-interest tracking** — only the pixels needed for the final output are computed.
- **Format-optimised decode** — JPEG, WebP, and TIFF decoders support shrink-on-load, so you can decode at reduced resolution directly.

These optimisations are transparent. The same pipeline code benefits from them without changes.

## Summary

| Aspect | Behaviour |
|--------|-----------|
| Operation execution | Deferred until terminal method |
| Terminal methods | `toFile()`, `toBuffer()`, `toArray()`, `toMemory()` |
| Multiple terminals | Each call re-evaluates the pipeline |
| Memory | Tile-based streaming; no full-resolution decode |
| Automatic fusion | Adjacent point operations are merged by VIPS |
