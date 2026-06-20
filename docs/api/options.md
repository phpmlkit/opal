# Options

Immutable configuration value objects for loading, saving, and text rendering.

---

## LoadOptions

Options controlling how an image is loaded from disk or buffer. Immutable — use `with*` methods to derive variants.

```php
final class LoadOptions
```

### Constructor

```php
public function __construct(
    public readonly ?int $page = null,
    public readonly ?int $n = null,
    public readonly bool $autoRotate = true,
    public readonly ?int $shrink = null,
    public readonly ?float $scale = null,
)
```

### Properties

| Property      | Type     | Default | Description                                           |
|---------------|----------|---------|-------------------------------------------------------|
| `$page`       | `?int`   | `null`  | Page number to load (for multipage formats like TIFF) |
| `$n`          | `?int`   | `null`  | Number of pages to load                               |
| `$autoRotate` | `bool`   | `true`  | Whether to auto-rotate based on EXIF orientation      |
| `$shrink`     | `?int`   | `null`  | Shrink-on-load factor (integer downscale)             |
| `$scale`      | `?float` | `null`  | Scale-on-load factor (float downscale)                |

### Static Methods

#### default()

Creates a LoadOptions with all defaults.

```php
public static function default(): self
```

### Mutator Methods

Each returns a **new** instance with the modified property.

#### withPage()

Set the page (zero-based) to load for multi-page formats.

```php
public function withPage(int $page): self
```

#### withN()

Set the number of pages to load.

```php
public function withN(int $n): self
```

#### withAutoRotate()

Enable or disable auto-rotation based on EXIF orientation.

```php
public function withAutoRotate(bool $autoRotate): self
```

#### withShrink()

Set the shrink-on-load factor (integer downscale).

```php
public function withShrink(int $factor): self
```

#### withScale()

Set the scale-on-load factor (float downscale).

```php
public function withScale(float $scale): self
```

### Internal Methods

#### toVipsOptions()

Convert to a VIPS-compatible options array.

```php
public function toVipsOptions(): array
```

**Returns:** Associative array for VIPS `newFromFile` / `newFromBuffer` options.

### Usage

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\LoadOptions;

// Default loading
$img = Image::fromFile('photo.jpg');

// Load second page of a TIFF
$options = (new LoadOptions())->withPage(1);
$tiffPage = Image::fromFile('multi-page.tif', $options);

// Load with shrink-on-load (faster thumbnails)
$options = LoadOptions::default()
    ->withShrink(2)
    ->withAutoRotate(false);
$img = Image::fromFile('large.jpg', $options);
```

---

## SaveOptions

Options controlling how an image is saved. Format-specific. Use one of the static factory methods to get a
pre-configured instance.

```php
final class SaveOptions
```

### Static Factory Methods

#### jpeg()

```php
public static function jpeg(
    int $quality = 85,
    bool $strip = true,
    bool $progressive = false,
): self
```

| Parameter      | Type   | Default | Description             |
|----------------|--------|---------|-------------------------|
| `$quality`     | `int`  | `85`    | JPEG quality (0–100)    |
| `$strip`       | `bool` | `true`  | Remove metadata         |
| `$progressive` | `bool` | `false` | Enable progressive JPEG |

#### png()

```php
public static function png(
    int $compression = 6,
    bool $strip = true,
    bool $interlace = false,
): self
```

| Parameter      | Type   | Default | Description                 |
|----------------|--------|---------|-----------------------------|
| `$compression` | `int`  | `6`     | PNG compression level (0–9) |
| `$strip`       | `bool` | `true`  | Remove metadata             |
| `$interlace`   | `bool` | `false` | Enable Adam7 interlacing    |

#### webp()

```php
public static function webp(
    int $quality = 80,
    bool $lossless = false,
    bool $strip = true,
): self
```

| Parameter   | Type   | Default | Description                 |
|-------------|--------|---------|-----------------------------|
| `$quality`  | `int`  | `80`    | WebP quality (0–100)        |
| `$lossless` | `bool` | `false` | Enable lossless compression |
| `$strip`    | `bool` | `true`  | Remove metadata             |

#### tiff()

```php
public static function tiff(int $quality = 75, bool $strip = false): self
```

| Parameter  | Type   | Default | Description     |
|------------|--------|---------|-----------------|
| `$quality` | `int`  | `75`    | TIFF quality    |
| `$strip`   | `bool` | `false` | Remove metadata |

#### avif()

```php
public static function avif(int $quality = 50, int $speed = 5): self
```

| Parameter  | Type  | Default | Description                                |
|------------|-------|---------|--------------------------------------------|
| `$quality` | `int` | `50`    | AVIF quality (0–100)                       |
| `$speed`   | `int` | `5`     | Encoding speed (0=slow/best, 8=fast/worst) |

#### heif()

```php
public static function heif(int $quality = 50): self
```

| Parameter  | Type  | Default | Description          |
|------------|-------|---------|----------------------|
| `$quality` | `int` | `50`    | HEIF quality (0–100) |

### Mutator Methods

#### withQuality()

Set the encoding quality (0–100).

```php
public function withQuality(int $quality): self
```

#### withStrip()

Set whether to strip metadata on save.

```php
public function withStrip(bool $strip): self
```

### Internal Methods

#### toVipsOptions()

Convert to a VIPS-compatible options array.

```php
public function toVipsOptions(): array
```

### Usage

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\SaveOptions;

$img = Image::fromFile('input.jpg');

// Save as JPEG with high quality
$img->toFile('output.jpg', SaveOptions::jpeg(quality: 95));

// Save as lossless WebP
$img->toFile('output.webp', SaveOptions::webp(lossless: true));

// Encode to PNG buffer with custom compression
$png = $img->toBuffer(
    ImageFormat::PNG,
    SaveOptions::png(compression: 9, interlace: true)
);
```

---

## TextOptions

Options controlling how text is rendered. Immutable — use `with*` methods to derive variants.

```php
final class TextOptions
```

### Constructor

```php
public function __construct(
    public readonly ?string $font = null,
    public readonly ?string $fontFile = null,
    public readonly ?int $fontSize = null,
    public readonly ?int $width = null,
    public readonly ?int $height = null,
    public readonly ?string $align = null,
    public readonly ?bool $justify = null,
    public readonly ?int $dpi = null,
    public readonly ?bool $rgba = null,
    public readonly ?int $spacing = null,
    public readonly ?string $wrap = null,
)
```

### Properties

| Property    | Type      | Default | Description                                                      |
|-------------|-----------|---------|------------------------------------------------------------------|
| `$font`     | `?string` | `null`  | Font family name (e.g. `'sans-serif'`, `'serif'`, `'monospace'`) |
| `$fontFile` | `?string` | `null`  | Path to a custom font file                                       |
| `$fontSize` | `?int`    | `12`    | Font size in points                                              |
| `$width`    | `?int`    | `null`  | Maximum width in pixels for text wrapping                        |
| `$height`   | `?int`    | `null`  | Maximum height in pixels                                         |
| `$align`    | `?string` | `null`  | Text alignment (`'left'`, `'centre'`, `'right'`)                 |
| `$justify`  | `?bool`   | `null`  | Whether to justify text                                          |
| `$dpi`      | `?int`    | `null`  | Rendering resolution in DPI                                      |
| `$rgba`     | `?bool`   | `null`  | Whether to render as RGBA                                        |
| `$spacing`  | `?int`    | `null`  | Line spacing in points                                           |
| `$wrap`     | `?string` | `null`  | Wrapping mode (`'word'`, `'char'`, `'word-char'`)                |

### Static Methods

#### default()

```php
public static function default(): self
```

Creates a TextOptions with no overrides; all values are `null` and the
underlying renderer's defaults are used (typically 12pt sans-serif).

### Font handling

Text rendering uses three orthogonal concepts:

- **font family** — a Pango/CSS family name like `"Helvetica"`, `"Times New Roman"`, or `"sans-serif"`
- **font file** — an optional path to a `.ttf` or `.otf` file, used when the family isn't installed system-wide
- **font size** — the size in points

Any of these can be set on their own. Missing values are filled in with
sensible defaults when the options are sent to the renderer, so
`->withFontSize(48)` alone works without the user having to also specify
a family.

The single entry point for both family and file is `withFont()` — pass
just a family for a system font, or a family + file path for a font that
isn't installed system-wide.

### Mutator Methods

Each returns a **new** instance with the modified property.

#### withFont()

Set the font family, and optionally a path to a font file for that family.

By default the family is resolved from the system's installed fonts.
Pass a `$fontFile` (a path to a `.ttf` or `.otf` file) to use a specific
font that isn't installed system-wide. The family name should match the
font's real family name (e.g. `"Inter"`, `"Caveat"`), not the filename.
If you don't know the family name, you can extract it from the font
file with a tool like `fc-query`.

Calling this method without a file clears any previously set file path
— the file is treated as belonging to the family, not as a separate
setting.

```php
public function withFont(string $fontFamily, ?string $fontFile = null): self
```

**Parameters:**

| Parameter     | Type      | Default | Description                                                |
|---------------|-----------|---------|------------------------------------------------------------|
| `$fontFamily` | `string`  | —       | A Pango/CSS family name (e.g. `"Helvetica"`, `"sans-serif"`) |
| `$fontFile`   | `?string` | `null`  | Optional path to a `.ttf` or `.otf` file                  |

#### withFontSize()

Set the font size in points. Can be called on its own; when no family
is set, the default family (`sans-serif`) is used.

```php
public function withFontSize(int $fontSize): self
```

#### withWidth()

Set the maximum text block width in pixels.

```php
public function withWidth(int $width): self
```

#### withHeight()

Set the maximum text block height in pixels.

```php
public function withHeight(int $height): self
```

#### withAlign()

Set alignment: `'left'`, `'centre'`, or `'right'`.

```php
public function withAlign(string $align): self
```

#### withJustify()

Enable or disable text justification.

```php
public function withJustify(bool $justify): self
```

#### withDpi()

Set rendering resolution in DPI.

```php
public function withDpi(int $dpi): self
```

#### withRgba()

Enable RGBA rendering for transparent backgrounds.

```php
public function withRgba(bool $rgba = true): self
```

#### withSpacing()

Set line spacing in points.

```php
public function withSpacing(int $spacing): self
```

#### withWrap()

Set wrapping mode: `'word'`, `'char'`, or `'word-char'`.

```php
public function withWrap(string $wrap): self
```

### Internal Methods

#### toVipsOptions()

Convert to a VIPS-compatible options array.

```php
public function toVipsOptions(): array
```

**Returns:** Associative array for VIPS `text` operation options.

### Usage

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\TextOptions;

// Create a text image with defaults
$textImg = Image::text('Hello World');

// Custom font family and size
$options = TextOptions::default()
    ->withFont('sans-serif')
    ->withFontSize(48)
    ->withWidth(800)
    ->withAlign('centre')
    ->withDpi(300);

$textImg = Image::text('Hello World', $options);

// Use a custom font file
$options = TextOptions::default()
    ->withFont('Caveat', '/path/to/Caveat.ttf')
    ->withFontSize(36);

$textImg = Image::text('Hello World', $options);

// Just a size — defaults to sans-serif if no family is specified
$options = TextOptions::default()->withFontSize(64);

// Draw text onto an existing image
$img = Image::fromFile('photo.jpg');
$img = $img->drawText('Hello', 100, 200, color: Color::white(), options: $options);
```
