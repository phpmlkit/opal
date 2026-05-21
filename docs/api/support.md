# Support Classes

Value objects and utilities in the `PhpMlKit\Opal` namespace.

---

## Color

Immutable RGBA color value. All components are integers in the range 0–255.

```php
final class Color
```

### Constructor

The constructor is private. Use the static factory methods to create instances.

```php
private function __construct(
    private readonly int $r,
    private readonly int $g,
    private readonly int $b,
    private readonly int $a,
)
```

**Throws:** `\InvalidArgumentException` if any component is outside 0–255.

### Static Factory Methods

#### rgb()

Create an opaque RGB color.

```php
public static function rgb(int $r, int $g, int $b): self
```

| Parameter | Type  | Description             |
|-----------|-------|-------------------------|
| `$r`      | `int` | Red component (0–255)   |
| `$g`      | `int` | Green component (0–255) |
| `$b`      | `int` | Blue component (0–255)  |

**Returns:** New Color with alpha = 255.

#### rgba()

Create an RGBA color with optional alpha.

```php
public static function rgba(int $r, int $g, int $b, int $a = 255): self
```

| Parameter | Type  | Default | Description                            |
|-----------|-------|---------|----------------------------------------|
| `$r`      | `int` | —       | Red component (0–255)                  |
| `$g`      | `int` | —       | Green component (0–255)                |
| `$b`      | `int` | —       | Blue component (0–255)                 |
| `$a`      | `int` | `255`   | Alpha component (0–255, 0=transparent) |

#### gray()

Create an opaque gray color with equal R, G, B values.

```php
public static function gray(int $value): self
```

| Parameter | Type  | Description        |
|-----------|-------|--------------------|
| `$value`  | `int` | Gray value (0–255) |

#### fromHex()

Create a color from a hex string.

```php
public static function fromHex(string $hex): self
```

| Parameter | Type     | Description                                          |
|-----------|----------|------------------------------------------------------|
| `$hex`    | `string` | Hex color string (`#rgb`, `#rrggbb`, or `#rrggbbaa`) |

**Throws:** `ImageException` for invalid hex characters, `\InvalidArgumentException` for invalid format.

### Named Color Factories

| Method          | Returns                     |
|-----------------|-----------------------------|
| `black()`       | `Color(0, 0, 0, 255)`       |
| `white()`       | `Color(255, 255, 255, 255)` |
| `transparent()` | `Color(0, 0, 0, 0)`         |
| `red()`         | `Color(255, 0, 0, 255)`     |
| `green()`       | `Color(0, 255, 0, 255)`     |
| `blue()`        | `Color(0, 0, 255, 255)`     |

### Accessor Methods

#### r()

```php
public function r(): int
```

**Returns:** Red component (0–255).

#### g()

```php
public function g(): int
```

**Returns:** Green component (0–255).

#### b()

```php
public function b(): int
```

**Returns:** Blue component (0–255).

#### a()

```php
public function a(): int
```

**Returns:** Alpha component (0–255).

### Conversion Methods

#### toArray()

```php
public function toArray(): array
```

**Returns:** `[R, G, B]` if fully opaque, or `[R, G, B, A]` if the alpha channel is less than 255.

#### toHex()

```php
public function toHex(): string
```

**Returns:** `#rrggbb` if fully opaque, or `#rrggbbaa` if the alpha channel is less than 255.

### Mutator Methods

#### withAlpha()

Create a new Color with a different alpha value.

```php
public function withAlpha(int $alpha): self
```

| Parameter | Type  | Description                 |
|-----------|-------|-----------------------------|
| `$alpha`  | `int` | New alpha component (0–255) |

### Usage

```php
use PhpMlKit\Opal\Color;

// Factory methods
$red = Color::red();
$custom = Color::rgb(100, 150, 200);
$semi = Color::rgba(255, 0, 0, 128);
$hex = Color::fromHex('#ff8800');

// Named colors
$bg = Color::black();
$fg = Color::white();
$invisible = Color::transparent();

// Access
echo $red->r();  // 255
echo $red->a();  // 255

// Conversion
$hex = Color::fromHex('#80ff8800');
echo $hex->toHex();    // #80ff8800
print_r($hex->toArray());  // [128, 255, 136, 0]

// Mutation (returns new instance)
$semiRed = Color::red()->withAlpha(128);

// Use in drawing
$img->drawRect(0, 0, 100, 100, Color::blue(), fill: true);
```

---

## ImageSize

Immutable 2-D size descriptor.

```php
final class ImageSize
```

### Constructor

```php
public function __construct(
    public readonly int $width,
    public readonly int $height,
)
```

**Throws:** `\InvalidArgumentException` if width or height is negative.

### Properties

| Property  | Type  | Description      |
|-----------|-------|------------------|
| `$width`  | `int` | Width in pixels  |
| `$height` | `int` | Height in pixels |

### Methods

#### __toString()

```php
public function __toString(): string
```

**Returns:** String in `"{width}x{height}"` format (e.g. `"1920x1080"`).

#### aspectRatio()

```php
public function aspectRatio(): float
```

**Returns:** Width divided by height. Returns 0 if height is 0.

#### pixels()

```php
public function pixels(): int
```

**Returns:** Total pixel count (width × height).

#### withWidth()

Create a new ImageSize with a different width, scaling height proportionally.

```php
public function withWidth(int $width): self
```

| Parameter | Type  | Description         |
|-----------|-------|---------------------|
| `$width`  | `int` | New width in pixels |

#### withHeight()

Create a new ImageSize with a different height, scaling width proportionally.

```php
public function withHeight(int $height): self
```

| Parameter | Type  | Description          |
|-----------|-------|----------------------|
| `$height` | `int` | New height in pixels |

#### scale()

Create a new ImageSize scaled by a uniform factor.

```php
public function scale(float $factor): self
```

| Parameter | Type    | Description                      |
|-----------|---------|----------------------------------|
| `$factor` | `float` | Scale factor (e.g. 0.5 to halve) |

#### contains()

Check whether this size fully contains another size.

```php
public function contains(self $other): bool
```

| Parameter | Type        | Description             |
|-----------|-------------|-------------------------|
| `$other`  | `ImageSize` | The other size to check |

**Returns:** `true` if both width and height are >= the other's dimensions.

#### toArray()

```php
public function toArray(): array
```

**Returns:** `['width' => int, 'height' => int]`

### Usage

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\ImageSize;

$img = Image::fromFile('photo.jpg');
$size = $img->size();

echo $size;                       // "1920x1080"
echo $size->aspectRatio();        // 1.777...
echo $size->pixels();             // 2073600

$half = $size->scale(0.5);        // 960x540
$same = new ImageSize(1920, 1080);
echo $size->contains($same);      // true
```

---

## BoundingBox

Immutable axis-aligned bounding box. All coordinates are in image pixel space (top-left origin).

```php
final class BoundingBox
```

### Constructor

```php
public function __construct(
    public readonly float $x,
    public readonly float $y,
    public readonly float $width,
    public readonly float $height,
)
```

**Throws:** `\InvalidArgumentException` if width or height is negative.

### Properties

| Property  | Type    | Description                         |
|-----------|---------|-------------------------------------|
| `$x`      | `float` | X-coordinate of the top-left corner |
| `$y`      | `float` | Y-coordinate of the top-left corner |
| `$width`  | `float` | Width of the box                    |
| `$height` | `float` | Height of the box                   |

### Static Factory Methods

#### fromCorners()

Create a BoundingBox from two corner points.

```php
public static function fromCorners(float $x1, float $y1, float $x2, float $y2): self
```

| Parameter | Type    | Description            |
|-----------|---------|------------------------|
| `$x1`     | `float` | X of the first corner  |
| `$y1`     | `float` | Y of the first corner  |
| `$x2`     | `float` | X of the second corner |
| `$y2`     | `float` | Y of the second corner |

#### fromCenter()

Create a BoundingBox from a center point and dimensions.

```php
public static function fromCenter(float $cx, float $cy, float $width, float $height): self
```

| Parameter | Type    | Description       |
|-----------|---------|-------------------|
| `$cx`     | `float` | Center X          |
| `$cy`     | `float` | Center Y          |
| `$width`  | `float` | Width of the box  |
| `$height` | `float` | Height of the box |

### Computed Properties

#### x2()

```php
public function x2(): float
```

**Returns:** Right edge X (x + width).

#### y2()

```php
public function y2(): float
```

**Returns:** Bottom edge Y (y + height).

#### centerX()

```php
public function centerX(): float
```

**Returns:** Center X coordinate.

#### centerY()

```php
public function centerY(): float
```

**Returns:** Center Y coordinate.

#### area()

```php
public function area(): float
```

**Returns:** Width × height.

### Operations

#### iou()

Compute Intersection over Union with another bounding box.

```php
public function iou(self $other): float
```

| Parameter | Type          | Description            |
|-----------|---------------|------------------------|
| `$other`  | `BoundingBox` | The other bounding box |

**Returns:** IoU score between 0.0 and 1.0.

#### scale()

Scale the bounding box by the given factors.

```php
public function scale(float $scaleX, float $scaleY): self
```

| Parameter | Type    | Description         |
|-----------|---------|---------------------|
| `$scaleX` | `float` | X-axis scale factor |
| `$scaleY` | `float` | Y-axis scale factor |

#### translate()

Translate (shift) the bounding box.

```php
public function translate(float $dx, float $dy): self
```

| Parameter | Type    | Description   |
|-----------|---------|---------------|
| `$dx`     | `float` | X-axis offset |
| `$dy`     | `float` | Y-axis offset |

#### expand()

Expand the bounding box by a uniform padding on all sides.

```php
public function expand(float $padding): self
```

| Parameter  | Type    | Description                           |
|------------|---------|---------------------------------------|
| `$padding` | `float` | Padding in pixels to add to all sides |

#### clamp()

Clamp the bounding box to fit within the given image dimensions.

```php
public function clamp(int $imageWidth, int $imageHeight): self
```

| Parameter      | Type  | Description              |
|----------------|-------|--------------------------|
| `$imageWidth`  | `int` | Image width to clamp to  |
| `$imageHeight` | `int` | Image height to clamp to |

**Returns:** New BoundingBox clamped within image bounds.

#### toInt()

Floor all coordinates to integer values.

```php
public function toInt(): self
```

**Returns:** New BoundingBox with floor-applied coordinates.

### Conversion Methods

#### toArray()

```php
public function toArray(): array
```

**Returns:** `['x' => float, 'y' => float, 'width' => float, 'height' => float]`

#### toCornersArray()

```php
public function toCornersArray(): array
```

**Returns:** `[x, y, x2, y2]` as an array of four floats.

### Usage

```php
use PhpMlKit\Opal\BoundingBox;
use PhpMlKit\Opal\Image;

$img = Image::fromFile('photo.jpg');

// Create from corners
$box = BoundingBox::fromCorners(100, 50, 300, 200);
echo $box->area();  // 200 * 150 = 30000

// Crop using bounding box
$cropped = $img->cropBoundingBox($box);

// Create from center
$box = BoundingBox::fromCenter(200, 200, 100, 100);

// IoU between two boxes
$a = BoundingBox::fromCorners(0, 0, 100, 100);
$b = BoundingBox::fromCorners(50, 50, 150, 150);
echo $a->iou($b);  // 0.1428...

// Scale for resized images
$scaledBox = $box->scale(0.5, 0.5);
```

---

## CompassDirection

Positioning direction used with padding and gravity operations.

```php
enum CompassDirection: string
```

### Cases

| Case         | Value          | Description      |
|--------------|----------------|------------------|
| `CENTRE`     | `'centre'`     | Center alignment |
| `NORTH`      | `'north'`      | Top-center       |
| `EAST`       | `'east'`       | Right-middle     |
| `SOUTH`      | `'south'`      | Bottom-center    |
| `WEST`       | `'west'`       | Left-middle      |
| `NORTH_EAST` | `'north_east'` | Top-right        |
| `SOUTH_EAST` | `'south_east'` | Bottom-right     |
| `SOUTH_WEST` | `'south_west'` | Bottom-left      |
| `NORTH_WEST` | `'north_west'` | Top-left         |

**Used in:** `Image::padToSize()`

### Usage

```php
use PhpMlKit\Opal\Image;
use PhpMlKit\Opal\CompassDirection;
use PhpMlKit\Opal\Color;

$img = Image::fromFile('photo.jpg');

// Pad to 1920x1080, position original in top-left
$padded = $img->padToSize(
    1920, 1080,
    background: Color::black(),
    direction: CompassDirection::NORTH_WEST
);

// Pad to square, center original
$squared = $img->padToSize(
    1024, 1024,
    background: Color::white(),
    direction: CompassDirection::CENTRE
);
```
