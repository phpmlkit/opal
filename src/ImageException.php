<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

class ImageException extends \RuntimeException
{
    public static function wrap(string $prefix, \Exception $e): static
    {
        $message = '' !== $e->getMessage() ? "{$prefix}: {$e->getMessage()}" : $prefix;

        // @phpstan-ignore-next-line
        return new static($message, 0, $e);
    }
}
