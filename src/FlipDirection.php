<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Axis to flip along.
 */
enum FlipDirection
{
    case Horizontal;
    case Vertical;
    case Both;
}
