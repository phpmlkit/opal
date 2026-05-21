# Exceptions

Opal throws specific exceptions for different error conditions.

## Exception Hierarchy

```
RuntimeException
└── ImageException (base)
    ├── FileNotFoundException
    ├── InvalidImageException
    ├── ShapeException
    └── UnsupportedFormatException
```

---

## ImageException

Base exception class for all image processing errors. Extends `\RuntimeException`.

```php
namespace PhpMlKit\Opal;

class ImageException extends \RuntimeException {}
```

**Common causes:**

- Color space conversion failures
- Resize, crop, flip, rotate failures
- Drawing/compositing errors
- Encoding/saving failures
- Any VIPS operation failure

---

## FileNotFoundException

Thrown when an image file does not exist at the specified path.

```php
namespace PhpMlKit\Opal\Exceptions;

class FileNotFoundException extends ImageException {}
```

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Exceptions\FileNotFoundException;

try {
    $img = Image::fromFile('/path/to/nonexistent.jpg');
} catch (FileNotFoundException $e) {
    echo "File not found: " . $e->getMessage();
}
```

**Used by:** `Image::fromFile()`, `Image::thumbnail()`

---

## InvalidImageException

Thrown when image data cannot be decoded or loaded.

```php
namespace PhpMlKit\Opal\Exceptions;

class InvalidImageException extends ImageException {}
```

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Exceptions\InvalidImageException;

try {
    $img = Image::fromFile('corrupted.jpg');
} catch (InvalidImageException $e) {
    echo "Invalid image: " . $e->getMessage();
}
```

**Common causes:**

- Corrupted file data
- Unsupported or unrecognized format
- Buffer does not contain valid image data
- Memory buffer has invalid dimensions

**Used by:** `Image::fromFile()`, `Image::fromBuffer()`, `Image::fromMemory()`, `Image::blank()`, `Image::thumbnail()`

---

## ShapeException

Thrown when image dimensions or shapes are invalid or incompatible.

```php
namespace PhpMlKit\Opal\Exceptions;

class ShapeException extends ImageException {}
```

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Exceptions\ShapeException;

try {
    $img = Image::blank(100, 100);
    $cropped = $img->crop(-1, 0, 50, 50);
} catch (ShapeException $e) {
    echo "Shape error: " . $e->getMessage();
}
```

**Common causes:**

- Negative or zero crop dimensions
- Crop region exceeds image bounds
- Resize dimensions less than 1
- `fromArray()` input has wrong number of dimensions
- Channel count outside valid range (1–4)

**Used by:** `Image::resize()`, `Image::resizeToWidth()`, `Image::resizeToHeight()`, `Image::crop()`,
`Image::centerCrop()`, `Image::fromArray()`

---

## UnsupportedFormatException

Thrown when an image format is not supported or recognized.

```php
namespace PhpMlKit\Opal\Exceptions;

class UnsupportedFormatException extends ImageException {}
```

```php
use PhpMlKit\Opal\ImageFormat;
use PhpMlKit\Opal\Exceptions\UnsupportedFormatException;

try {
    $format = ImageFormat::fromExtension('ico');
} catch (UnsupportedFormatException $e) {
    echo "Unsupported format: " . $e->getMessage();
}
```

**Common causes:**

- Unknown file extension in `ImageFormat::fromExtension()`
- Extension does not map to a supported format

**Used by:** `ImageFormat::fromExtension()`, `ImageFormat::toExtension()`, `ImageFormat::suffix()`

---

## Summary Table

| Exception                    | Extends             | When Thrown                                        | Used By                                                                |
|------------------------------|---------------------|----------------------------------------------------|------------------------------------------------------------------------|
| `ImageException`             | `\RuntimeException` | Base for all image errors, VIPS operation failures | Most `Image` methods                                                   |
| `FileNotFoundException`      | `ImageException`    | File does not exist at path                        | `fromFile()`, `thumbnail()`                                            |
| `InvalidImageException`      | `ImageException`    | Cannot decode/load image data                      | `fromFile()`, `fromBuffer()`, `fromMemory()`, `blank()`, `thumbnail()` |
| `ShapeException`             | `ImageException`    | Invalid or incompatible dimensions                 | `resize()`, `crop()`, `centerCrop()`, `fromArray()`                    |
| `UnsupportedFormatException` | `ImageException`    | Unrecognized format/extension                      | `ImageFormat` methods                                                  |

---

## Handling Exceptions

### Basic Try-Catch

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ImageException;

try {
    $img = Image::fromFile('input.jpg');
    $result = $img->resize(800, 600);
    $result->toFile('output.jpg');
} catch (ImageException $e) {
    echo "Image error: " . $e->getMessage();
}
```

### Specific Exception Handling

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\Exceptions\FileNotFoundException;
use PhpMlKit\Opal\Exceptions\InvalidImageException;
use PhpMlKit\Opal\ImageException;

try {
    $img = Image::fromFile($path);
} catch (FileNotFoundException $e) {
    echo "File missing: " . $e->getMessage();
} catch (InvalidImageException $e) {
    echo "Bad image: " . $e->getMessage();
} catch (ImageException $e) {
    echo "Other image error: " . $e->getMessage();
}
```

### Catching the Base Type

Since all exceptions extend `ImageException`, you can catch the base type:

```php
try {
    $img = Image::fromFile('photo.jpg');
    $img->toFile('output.jpg');
} catch (ImageException $e) {
    // Catches ImageException and all subclasses
    echo $e->getMessage();
}
```
