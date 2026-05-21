<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Resampling kernel used for image resize/scale operations.
 *
 * Maps directly to VIPS VipsKernel.  The default kernel across this library
 * is Lanczos3, which offers high-quality down- and up-sampling for most use
 * cases.  Use a lighter kernel (e.g. Linear or Nearest) when throughput
 * matters more than fidelity.
 */
enum Kernel: string
{
    /** Nearest-neighbour — fast, blocky. Useful for pixel-art. */
    case Nearest = 'nearest';

    /** Bilinear interpolation — smooth, moderate quality. */
    case Linear = 'linear';

    /** Bicubic interpolation — sharper than linear. */
    case Cubic = 'cubic';

    /** Mitchell-Netravalli filter — good general-purpose. */
    case Mitchell = 'mitchell';

    /** 2-lobe Lanczos — sharp with mild ringing. */
    case Lanczos2 = 'lanczos2';

    /** 3-lobe Lanczos — high quality, default kernel. */
    case Lanczos3 = 'lanczos3';

    /** MKS 211 kernel — 2-lobe MKS filter. */
    case Mks211 = 'mks211';

    /** MKS 213 kernel — 3-lobe MKS filter. */
    case Mks213 = 'mks213';
}
