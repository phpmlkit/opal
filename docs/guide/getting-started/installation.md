---
title: Installation
---

# Installation

## Requirements

- **PHP**: Version 8.2 or higher
- **FFI Extension**: Must be enabled in your PHP installation
- **Composer**: For dependency management

## Check FFI Extension

```bash
php -m | grep FFI
```

If you don't see "FFI" in the output, you need to enable it. Edit your `php.ini` and add `extension=ffi`.

## Install via Composer

```bash
composer require phpmlkit/opal
```

::: warning Platform-dependent package
This library ships with pre-built native binaries. Run `composer require` on the **same platform** where you intend to use the library (Linux x86_64/ARM64, macOS x86_64/ARM64, Windows x64).
:::

## Verifying Installation

Create a test script:

```php
<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use PhpMlKit\Opal\Image;

$image = Image::blank(100, 100);
printf("Image created: %dx%d\n", $image->width(), $image->height());

// Try a thumbnail
$thumb = Image::thumbnail(__DIR__.'/vendor/phpmlkit/opal/examples/images/cats.jpg', 200);
printf("Thumbnail: %dx%d\n", $thumb->width(), $thumb->height());

echo "✓ Opal is working correctly!\n";
```

Run it:

```bash
php test.php
```

Expected output:

```
Image created: 100x100
Thumbnail: 200x150
✓ Opal is working correctly!
```

## Troubleshooting

### "FFI extension not loaded"

Make sure FFI is enabled in the `php.ini` that the CLI uses:

```bash
php --ini
```

Edit that specific file and ensure `extension=ffi` is present (without the semicolon).

### "Platform package not found"

If you see errors about platform-specific binaries:

1. Check your platform is supported (Linux x86_64/ARM64, macOS x86_64/ARM64, Windows x86_64)
2. Try clearing Composer cache: `composer clear-cache`
3. Reinstall: `composer require phpmlkit/opal`

## Next Steps

- **[Quick Start](/guide/getting-started/quick-start)** — your first image
- **[What is Opal?](/guide/getting-started/what-is-opal)** — understand the architecture
