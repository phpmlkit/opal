# Enums

All enumerations in the `PhpMlKit\Opal` namespace.

---

## Kernel

Resampling kernel used for image resize/scale operations. Maps directly to VIPS `VipsKernel`.

**Default across the library is `Lanczos3`**, which offers high-quality down- and up-sampling. Use a lighter kernel (
e.g. `Linear` or `Nearest`) when throughput matters more than fidelity.

```php
enum Kernel: string
```

### Cases

| Case       | Value        | Description                                             |
|------------|--------------|---------------------------------------------------------|
| `Nearest`  | `'nearest'`  | Nearest-neighbour — fast, blocky. Useful for pixel-art. |
| `Linear`   | `'linear'`   | Bilinear interpolation — smooth, moderate quality.      |
| `Cubic`    | `'cubic'`    | Bicubic interpolation — sharper than linear.            |
| `Mitchell` | `'mitchell'` | Mitchell-Netravalli filter — good general-purpose.      |
| `Lanczos2` | `'lanczos2'` | 2-lobe Lanczos — sharp with mild ringing.               |
| `Lanczos3` | `'lanczos3'` | 3-lobe Lanczos — high quality, default kernel.          |
| `Mks211`   | `'mks211'`   | MKS 211 kernel — 2-lobe MKS filter.                     |
| `Mks213`   | `'mks213'`   | MKS 213 kernel — 3-lobe MKS filter.                     |

**Used in:** `Image::resize()`, `Image::resizeToWidth()`, `Image::resizeToHeight()`, `Image::scale()`

---

## ColorSpace

Supported color spaces. Backed by vips interpretation strings.

```php
enum ColorSpace: string
```

### Cases

| Case        | Value    | Bands |
|-------------|----------|-------|
| `RGB`       | `'rgb'`  | 3     |
| `RGBA`      | `'rgba'` | 4     |
| `BGR`       | `'bgr'`  | 3     |
| `BGRA`      | `'bgra'` | 4     |
| `Grayscale` | `'grey'` | 1     |
| `Lab`       | `'lab'`  | 3     |
| `HSV`       | `'hsv'`  | 3     |
| `CMYK`      | `'cmyk'` | 4     |

### Methods

#### bands()

Returns the number of channels for a newly created image in this space.

```php
public function bands(): int
```

#### fromVipsInterpretation()

Create a ColorSpace from a VIPS interpretation string.

```php
public static function fromVipsInterpretation(string $interpretation): ColorSpace
```

#### toVipsInterpretation()

Convert to a VIPS interpretation string.

```php
public function toVipsInterpretation(): string
```

---

## BandFormat

Enumeration of supported numeric formats for pixel bands. Each value represents a data type used to store per-channel
pixel information.

```php
enum BandFormat: int
```

### Cases

| Case        | Value | Storage (bytes) | NDArray DType |
|-------------|-------|-----------------|---------------|
| `UCHAR`     | `0`   | 1               | `UInt8`       |
| `CHAR`      | `1`   | 1               | `Int8`        |
| `USHORT`    | `2`   | 2               | `UInt16`      |
| `SHORT`     | `3`   | 2               | `Int16`       |
| `UINT`      | `4`   | 4               | `UInt32`      |
| `INT`       | `5`   | 4               | `Int32`       |
| `FLOAT`     | `6`   | 4               | `Float32`     |
| `COMPLEX`   | `7`   | 8               | `Complex64`   |
| `DOUBLE`    | `8`   | 8               | `Float64`     |
| `DPCOMPLEX` | `9`   | 16              | `Complex128`  |

### Methods

#### fromString()

```php
public static function fromString(string $format): self
```

#### toString()

```php
public function toString(): string
```

#### fromDtype()

Create from an NDArray DType.

```php
public static function fromDtype(DType $dtype): self
```

**Throws:** `\InvalidArgumentException` if the dtype is unsupported.

#### toDtype()

Convert to an NDArray DType.

```php
public function toDtype(): DType
```

#### storageBytes()

Returns the number of bytes per pixel band.

```php
public function storageBytes(): int
```

---

## FlipDirection

Axis to flip an image along.

```php
enum FlipDirection
```

### Cases

| Case         | Description          |
|--------------|----------------------|
| `Horizontal` | Flip left-to-right   |
| `Vertical`   | Flip top-to-bottom   |
| `Both`       | Flip along both axes |

**Used in:** `Image::flip()`

---

## ChannelFormat

Memory layout for multichannel image tensors when importing/exporting NDArray.

```php
enum ChannelFormat
```

### Cases

| Case  | Layout                      | Description                      |
|-------|-----------------------------|----------------------------------|
| `HWC` | `[height, width, channels]` | TensorFlow / NumPy / PIL default |
| `CHW` | `[channels, height, width]` | PyTorch / ONNX vision default    |

**Used in:** `Image::fromArray()`, `Image::toArray()`

---

## ImageFormat

Image file container formats.

```php
enum ImageFormat: string
```

### Cases

| Case   | Value    | Extension |
|--------|----------|-----------|
| `JPEG` | `'jpeg'` | `.jpg`    |
| `PNG`  | `'png'`  | `.png`    |
| `WebP` | `'webp'` | `.webp`   |
| `TIFF` | `'tiff'` | `.tif`    |
| `GIF`  | `'gif'`  | `.gif`    |
| `BMP`  | `'bmp'`  | `.bmp`    |
| `AVIF` | `'avif'` | `.avif`   |
| `HEIF` | `'heif'` | `.heif`   |

### Methods

#### fromExtension()

```php
public static function fromExtension(string $extension): self
```

**Throws:** `UnsupportedFormatException` if the extension is not recognized.

#### toExtension()

```php
public function toExtension(): string
```

#### suffix()

Returns the file suffix including the leading dot.

```php
public function suffix(): string
```
