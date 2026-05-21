<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

use PhpMlKit\NDArray\DType;

/**
 * Enumeration of supported numeric formats for pixel bands.
 * Each value represents a data type used to store per-channel pixel information.
 * Used for mapping band storage to NDArray/FFI/native interop in Image processing.
 */
enum BandFormat: int
{
    case UCHAR = 0;
    case CHAR = 1;
    case USHORT = 2;
    case SHORT = 3;
    case UINT = 4;
    case INT = 5;
    case FLOAT = 6;
    case COMPLEX = 7;
    case DOUBLE = 8;
    case DPCOMPLEX = 9;

    public static function fromString(string $format): self
    {
        return match ($format) {
            'uchar' => self::UCHAR,
            'char' => self::CHAR,
            'ushort' => self::USHORT,
            'short' => self::SHORT,
            'uint' => self::UINT,
            'int' => self::INT,
            'float' => self::FLOAT,
            'complex' => self::COMPLEX,
            'double' => self::DOUBLE,
            'dpcomplex' => self::DPCOMPLEX,
            default => self::FLOAT,
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::UCHAR => 'uchar',
            self::CHAR => 'char',
            self::USHORT => 'ushort',
            self::SHORT => 'short',
            self::UINT => 'uint',
            self::INT => 'int',
            self::FLOAT => 'float',
            self::COMPLEX => 'complex',
            self::DOUBLE => 'double',
            self::DPCOMPLEX => 'dpcomplex',
        };
    }

    public static function fromDtype(DType $dtype): BandFormat
    {
        return match ($dtype) {
            DType::UInt8 => self::UCHAR,
            DType::UInt16 => self::USHORT,
            DType::Int16 => self::SHORT,
            DType::UInt32 => self::UINT,
            DType::Int32 => self::INT,
            DType::Float32 => self::FLOAT,
            DType::Complex64 => self::COMPLEX,
            DType::Float64 => self::DOUBLE,
            DType::Complex128 => self::DPCOMPLEX,
            default => throw new \InvalidArgumentException("Unsupported NDArray dtype: {$dtype->name}"),
        };
    }

    public function toDtype(): DType
    {
        return match ($this) {
            self::UCHAR => DType::UInt8,
            self::CHAR => DType::Int8,
            self::USHORT => DType::UInt16,
            self::SHORT => DType::Int16,
            self::UINT => DType::UInt32,
            self::INT => DType::Int32,
            self::FLOAT => DType::Float32,
            self::COMPLEX => DType::Complex64,
            self::DOUBLE => DType::Float64,
            self::DPCOMPLEX => DType::Complex128,
        };
    }

    public function storageBytes(): int
    {
        return match ($this) {
            self::UCHAR => 1,
            self::CHAR => 1,
            self::USHORT => 2,
            self::SHORT => 2,
            self::UINT => 4,
            self::INT => 4,
            self::FLOAT => 4,
            self::COMPLEX => 8,
            self::DOUBLE => 8,
            self::DPCOMPLEX => 16,
        };
    }
}
